<?php

namespace Drupal\Tests\jsonapi\Functional;

use Behat\Mink\Driver\BrowserKitDriver;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Random;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheableResponseInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Entity\ContentEntityNullStorage;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\BooleanItem;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\Core\Url;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\jsonapi\Normalizer\HttpExceptionNormalizer;
use Drupal\jsonapi\ResourceResponse;
use Drupal\path\Plugin\Field\FieldType\PathItem;
use Drupal\Tests\BrowserTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\RoleInterface;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Subclass this for every JSON API resource type.
 */
abstract class ResourceTestBase extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['jsonapi', 'basic_auth', 'rest_test', 'text'];

  /**
   * The tested entity type.
   *
   * @var string
   */
  protected static $entityTypeId = NULL;

  /**
   * The name of the tested JSON API resource type.
   *
   * @var string
   */
  protected static $resourceTypeName = NULL;

  /**
   * The fields that are protected against modification during PATCH requests.
   *
   * @var string[]
   */
  protected static $patchProtectedFieldNames;

  /**
   * The entity ID for the first created entity in testPost().
   *
   * The default value of 2 should work for most content entities.
   *
   * @var string|int
   *
   * @see ::testPostIndividual()
   */
  protected static $firstCreatedEntityId = 2;

  /**
   * The entity ID for the second created entity in testPost().
   *
   * The default value of 3 should work for most content entities.
   *
   * @var string|int
   *
   * @see ::testPostIndividual()
   */
  protected static $secondCreatedEntityId = 3;

  /**
   * Optionally specify which field is the 'label' field.
   *
   * Some entities specify a 'label_callback', but not a 'label' entity key.
   * For example: User.
   *
   * @var string|null
   *
   * @see ::getInvalidNormalizedEntityToCreate()
   */
  protected static $labelFieldName = NULL;

  /**
   * The entity being tested.
   *
   * @var \Drupal\Core\Entity\EntityInterface
   */
  protected $entity;

  /**
   * The account to use for authentication.
   *
   * @var null|\Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * The entity storage.
   *
   * @var \Drupal\Core\Entity\EntityStorageInterface
   */
  protected $entityStorage;

  /**
   * The serializer service.
   *
   * @var \Symfony\Component\Serializer\Serializer
   */
  protected $serializer;

  /**
   * The Entity-to-JSON-API service.
   *
   * @var \Drupal\jsonapi\EntityToJsonApi
   */
  protected $entityToJsonApi;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->serializer = $this->container->get('jsonapi.serializer_do_not_use_removal_imminent');
    $this->entityToJsonApi = $this->container->get('jsonapi.entity.to_jsonapi');

    // Ensure the anonymous user role has no permissions at all.
    $user_role = Role::load(RoleInterface::ANONYMOUS_ID);
    foreach ($user_role->getPermissions() as $permission) {
      $user_role->revokePermission($permission);
    }
    $user_role->save();
    assert([] === $user_role->getPermissions(), 'The anonymous user role has no permissions at all.');

    // Ensure the authenticated user role has no permissions at all.
    $user_role = Role::load(RoleInterface::AUTHENTICATED_ID);
    foreach ($user_role->getPermissions() as $permission) {
      $user_role->revokePermission($permission);
    }
    $user_role->save();
    assert([] === $user_role->getPermissions(), 'The authenticated user role has no permissions at all.');

    // Create an account, which tests will use. Also ensure the @current_user
    // service uses this account, to ensure the @jsonapi.entity.to_jsonapi
    // service that we use to generate expectations matching that of this user.
    $this->account = $this->createUser();
    $this->container->get('current_user')->setAccount($this->account);

    // Create an entity.
    $this->entityStorage = $this->container->get('entity_type.manager')
      ->getStorage(static::$entityTypeId);
    $this->entity = $this->createEntity();
    \Drupal::service('jsonapi.resource_type.repository')->clearCachedDefinitions();
    \Drupal::service('router.builder')->rebuild();

    if ($this->entity instanceof FieldableEntityInterface) {
      // Add access-protected field.
      FieldStorageConfig::create([
        'entity_type' => static::$entityTypeId,
        'field_name' => 'field_rest_test',
        'type' => 'text',
      ])
        ->setCardinality(1)
        ->save();
      FieldConfig::create([
        'entity_type' => static::$entityTypeId,
        'field_name' => 'field_rest_test',
        'bundle' => $this->entity->bundle(),
      ])
        ->setLabel('Test field')
        ->setTranslatable(FALSE)
        ->save();

      // @todo Do this unconditionally when JSON API requires Drupal 8.5 or newer.
      if (floatval(\Drupal::VERSION) >= 8.5) {
        // Add multi-value field.
        FieldStorageConfig::create([
          'entity_type' => static::$entityTypeId,
          'field_name' => 'field_rest_test_multivalue',
          'type' => 'string',
        ])
          ->setCardinality(3)
          ->save();
        FieldConfig::create([
          'entity_type' => static::$entityTypeId,
          'field_name' => 'field_rest_test_multivalue',
          'bundle' => $this->entity->bundle(),
        ])
          ->setLabel('Test field: multi-value')
          ->setTranslatable(FALSE)
          ->save();
      }

      // Reload entity so that it has the new field.
      $reloaded_entity = $this->entityStorage->loadUnchanged($this->entity->id());
      // Some entity types are not stored, hence they cannot be reloaded.
      if ($reloaded_entity !== NULL) {
        $this->entity = $reloaded_entity;

        // Set a default value on the fields.
        $this->entity->set('field_rest_test', ['value' => 'All the faith he had had had had no effect on the outcome of his life.']);
        // @todo Do this unconditionally when JSON API requires Drupal 8.5 or newer.
        if (floatval(\Drupal::VERSION) >= 8.5) {
          $this->entity->set('field_rest_test_multivalue', [['value' => 'One'], ['value' => 'Two']]);
        }
        $this->entity->save();
      }
    }
  }

  /**
   * Creates the entity to be tested.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The entity to be tested.
   */
  abstract protected function createEntity();

  /**
   * Returns the expected JSON API document for the entity.
   *
   * @see ::createEntity()
   *
   * @return array
   *   A JSON API response document.
   */
  abstract protected function getExpectedDocument();

  /**
   * Returns the JSON API POST document.
   *
   * @see ::testPostIndividual()
   *
   * @return array
   *   A JSON API request document.
   */
  abstract protected function getPostDocument();

  /**
   * Returns the JSON API PATCH document.
   *
   * By default, reuses ::getPostDocument(), which works fine for most entity
   * types. A counter example: the 'comment' entity type.
   *
   * @see ::testPatchIndividual()
   *
   * @return array
   *   A JSON API request document.
   */
  protected function getPatchDocument() {
    return NestedArray::mergeDeep(['data' => ['id' => $this->entity->uuid()]], $this->getPostDocument());
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedUnauthorizedAccessCacheability() {
    return (new CacheableMetadata())
      ->setCacheTags(['4xx-response', 'http_response'])
      ->setCacheContexts(['user.permissions']);
  }

  /**
   * The expected cache tags for the GET/HEAD response of the test entity.
   *
   * @see ::testGetIndividual()
   *
   * @return string[]
   *   A set of cache tags.
   */
  protected function getExpectedCacheTags() {
    $expected_cache_tags = [
      'http_response',
    ];
    return Cache::mergeTags($expected_cache_tags, $this->entity->getCacheTags());
  }

  /**
   * The expected cache contexts for the GET/HEAD response of the test entity.
   *
   * @see ::testGetIndividual()
   *
   * @return string[]
   *   A set of cache contexts.
   */
  protected function getExpectedCacheContexts() {
    return [
      // Cache contexts for JSON API URL query parameters.
      'url.query_args:fields',
      'url.query_args:filter',
      'url.query_args:include',
      'url.query_args:page',
      'url.query_args:sort',
      // Drupal defaults.
      'url.site',
      'user.permissions',
    ];
  }

  /**
   * Sets up the necessary authorization.
   *
   * In case of a test verifying publicly accessible REST resources: grant
   * permissions to the anonymous user role.
   *
   * In case of a test verifying behavior when using a particular authentication
   * provider: create a user with a particular set of permissions.
   *
   * Because of the $method parameter, it's possible to first set up
   * authentication for only GET, then add POST, et cetera. This then also
   * allows for verifying a 403 in case of missing authorization.
   *
   * @param string $method
   *   The HTTP method for which to set up authentication.
   *
   * @see ::grantPermissionsToAnonymousRole()
   * @see ::grantPermissionsToAuthenticatedRole()
   */
  abstract protected function setUpAuthorization($method);

  /**
   * Return the expected error message.
   *
   * @param string $method
   *   The HTTP method (GET, POST, PATCH, DELETE).
   *
   * @return string
   *   The error string.
   */
  protected function getExpectedUnauthorizedAccessMessage($method) {
    $permission = $this->entity->getEntityType()->getAdminPermission();
    if ($permission !== FALSE) {
      return "The '{$permission}' permission is required.";
    }

    return NULL;
  }

  /**
   * Grants permissions to the authenticated role.
   *
   * @param string[] $permissions
   *   Permissions to grant.
   */
  protected function grantPermissionsToTestedRole(array $permissions) {
    $this->grantPermissions(Role::load(RoleInterface::AUTHENTICATED_ID), $permissions);
  }

  /**
   * Performs a HTTP request. Wraps the Guzzle HTTP client.
   *
   * Why wrap the Guzzle HTTP client? Because we want to keep the actual test
   * code as simple as possible, and hence not require them to specify the
   * 'http_errors = FALSE' request option, nor do we want them to have to
   * convert Drupal Url objects to strings.
   *
   * We also don't want to follow redirects automatically, to ensure these tests
   * are able to detect when redirects are added or removed.
   *
   * @param string $method
   *   HTTP method.
   * @param \Drupal\Core\Url $url
   *   URL to request.
   * @param array $request_options
   *   Request options to apply.
   *
   * @return \Psr\Http\Message\ResponseInterface
   *   The response.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function request($method, Url $url, array $request_options) {
    $request_options[RequestOptions::HTTP_ERRORS] = FALSE;
    $request_options[RequestOptions::ALLOW_REDIRECTS] = FALSE;
    $request_options = $this->decorateWithXdebugCookie($request_options);
    $client = $this->getSession()->getDriver()->getClient()->getClient();
    return $client->request($method, $url->setAbsolute(TRUE)->toString(), $request_options);
  }

  /**
   * Asserts that a resource response has the given status code and body.
   *
   * @param int $expected_status_code
   *   The expected response status.
   * @param string|false $expected_body
   *   The expected response body. FALSE in case this should not be asserted.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The response to assert.
   * @param string[]|false $expected_cache_tags
   *   (optional) The expected cache tags in the X-Drupal-Cache-Tags response
   *   header, or FALSE if that header should be absent. Defaults to FALSE.
   * @param string[]|false $expected_cache_contexts
   *   (optional) The expected cache contexts in the X-Drupal-Cache-Contexts
   *   response header, or FALSE if that header should be absent. Defaults to
   *   FALSE.
   * @param string|false $expected_page_cache_header_value
   *   (optional) The expected X-Drupal-Cache response header value, or FALSE if
   *   that header should be absent. Possible strings: 'MISS', 'HIT'. Defaults
   *   to FALSE.
   * @param string|false $expected_dynamic_page_cache_header_value
   *   (optional) The expected X-Drupal-Dynamic-Cache response header value, or
   *   FALSE if that header should be absent. Possible strings: 'MISS', 'HIT'.
   *   Defaults to FALSE.
   */
  protected function assertResourceResponse($expected_status_code, $expected_body, ResponseInterface $response, $expected_cache_tags = FALSE, $expected_cache_contexts = FALSE, $expected_page_cache_header_value = FALSE, $expected_dynamic_page_cache_header_value = FALSE) {
    $this->assertSame($expected_status_code, $response->getStatusCode());
    if ($expected_status_code === 204) {
      // DELETE responses should not include a Content-Type header. But Apache
      // sets it to 'text/html' by default. We also cannot detect the presence
      // of Apache either here in the CLI. For now having this documented here
      // is all we can do.
      /* $this->assertSame(FALSE, $response->hasHeader('Content-Type')); */
      $this->assertSame('', (string) $response->getBody());
    }
    else {
      $this->assertSame(['application/vnd.api+json'], $response->getHeader('Content-Type'));
      if ($expected_body !== FALSE) {
        $this->assertSame($expected_body, (string) $response->getBody());
      }
    }

    // Expected cache tags: X-Drupal-Cache-Tags header.
    $this->assertSame($expected_cache_tags !== FALSE, $response->hasHeader('X-Drupal-Cache-Tags'));
    if (is_array($expected_cache_tags)) {
      $this->assertSame($expected_cache_tags, explode(' ', $response->getHeader('X-Drupal-Cache-Tags')[0]));
    }

    // Expected cache contexts: X-Drupal-Cache-Contexts header.
    $this->assertSame($expected_cache_contexts !== FALSE, $response->hasHeader('X-Drupal-Cache-Contexts'));
    if (is_array($expected_cache_contexts)) {
      $this->assertSame($expected_cache_contexts, explode(' ', $response->getHeader('X-Drupal-Cache-Contexts')[0]));
    }

    // Expected Page Cache header value: X-Drupal-Cache header.
    if ($expected_page_cache_header_value !== FALSE) {
      $this->assertTrue($response->hasHeader('X-Drupal-Cache'));
      $this->assertSame($expected_page_cache_header_value, $response->getHeader('X-Drupal-Cache')[0]);
    }
    else {
      $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    }

    // Expected Dynamic Page Cache header value: X-Drupal-Dynamic-Cache header.
    if ($expected_dynamic_page_cache_header_value !== FALSE) {
      $this->assertTrue($response->hasHeader('X-Drupal-Dynamic-Cache'));
      $this->assertSame($expected_dynamic_page_cache_header_value, $response->getHeader('X-Drupal-Dynamic-Cache')[0]);
    }
    else {
      $this->assertFalse($response->hasHeader('X-Drupal-Dynamic-Cache'));
    }
  }

  /**
   * Asserts that a resource error response has the given message.
   *
   * @param int $expected_status_code
   *   The expected response status.
   * @param string $expected_message
   *   The expected error message.
   * @param \Psr\Http\Message\ResponseInterface $response
   *   The error response to assert.
   * @param string|false $pointer
   *   The expected JSON Pointer to the associated entity in the request
   *   document. See http://jsonapi.org/format/#error-objects.
   * @param string[]|false $expected_cache_tags
   *   (optional) The expected cache tags in the X-Drupal-Cache-Tags response
   *   header, or FALSE if that header should be absent. Defaults to FALSE.
   * @param string[]|false $expected_cache_contexts
   *   (optional) The expected cache contexts in the X-Drupal-Cache-Contexts
   *   response header, or FALSE if that header should be absent. Defaults to
   *   FALSE.
   * @param string|false $expected_page_cache_header_value
   *   (optional) The expected X-Drupal-Cache response header value, or FALSE if
   *   that header should be absent. Possible strings: 'MISS', 'HIT'. Defaults
   *   to FALSE.
   * @param string|false $expected_dynamic_page_cache_header_value
   *   (optional) The expected X-Drupal-Dynamic-Cache response header value, or
   *   FALSE if that header should be absent. Possible strings: 'MISS', 'HIT'.
   *   Defaults to FALSE.
   */
  protected function assertResourceErrorResponse($expected_status_code, $expected_message, ResponseInterface $response, $pointer = FALSE, $expected_cache_tags = FALSE, $expected_cache_contexts = FALSE, $expected_page_cache_header_value = FALSE, $expected_dynamic_page_cache_header_value = FALSE) {
    $expected_error = [];
    if (!empty(Response::$statusTexts[$expected_status_code])) {
      $expected_error['title'] = Response::$statusTexts[$expected_status_code];
    }
    $expected_error['status'] = $expected_status_code;
    $expected_error['detail'] = $expected_message;
    if ($info_url = HttpExceptionNormalizer::getInfoUrl($expected_status_code)) {
      $expected_error['links']['info'] = $info_url;
    }
    // @todo Remove in https://www.drupal.org/project/jsonapi/issues/2934362.
    $expected_error['code'] = 0;
    if ($pointer !== FALSE) {
      $expected_error['source']['pointer'] = $pointer;
    }

    $expected = [
      'errors' => [
        0 => $expected_error,
      ],
    ];
    $this->assertResourceResponse($expected_status_code, Json::encode($expected), $response, $expected_cache_tags, $expected_cache_contexts, $expected_page_cache_header_value, $expected_dynamic_page_cache_header_value);
  }

  /**
   * Adds the Xdebug cookie to the request options.
   *
   * @param array $request_options
   *   The request options.
   *
   * @return array
   *   Request options updated with the Xdebug cookie if present.
   */
  protected function decorateWithXdebugCookie(array $request_options) {
    $session = $this->getSession();
    $driver = $session->getDriver();
    if ($driver instanceof BrowserKitDriver) {
      $client = $driver->getClient();
      foreach ($client->getCookieJar()->all() as $cookie) {
        if (isset($request_options[RequestOptions::HEADERS]['Cookie'])) {
          $request_options[RequestOptions::HEADERS]['Cookie'] .= '; ' . $cookie->getName() . '=' . $cookie->getValue();
        }
        else {
          $request_options[RequestOptions::HEADERS]['Cookie'] = $cookie->getName() . '=' . $cookie->getValue();
        }
      }
    }
    return $request_options;
  }

  /**
   * Makes the JSON API document violate the spec by omitting the resource type.
   *
   * @param array $document
   *   A JSON API document.
   *
   * @return array
   *   The same JSON API document, without its resource type.
   */
  protected function removeResourceTypeFromDocument(array $document) {
    unset($document['data']['type']);
    return $document;
  }

  /**
   * Makes the given JSON API document invalid.
   *
   * @param array $document
   *   A JSON API document.
   *
   * @return array
   *   The updated JSON API document, now invalid.
   */
  protected function makeLabelFieldNormalizationInvalid(array $document) {
    // Add a second label to this entity to make it invalid.
    $label_field = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName;
    $document['data']['attributes'][$label_field] = [
      0 => $document['data']['attributes'][$label_field],
      1 => 'Second Title',
    ];

    return $document;
  }

  /**
   * Tests GETting an individual resource, plus edge cases to ensure good DX.
   */
  public function testGetIndividual() {
    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), [static::$entityTypeId => $this->entity->uuid()]);
    /* $url = $this->entity->toUrl('jsonapi'); */
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions('GET'));

    // DX: 403 when unauthorized.
    $response = $this->request('GET', $url, $request_options);
    $expected_403_cacheability = $this->getExpectedUnauthorizedAccessCacheability();
    $reason = $this->getExpectedUnauthorizedAccessMessage('GET');
    // @todo Remove $expected + assertResourceResponse() in favor of the commented line below once https://www.drupal.org/project/jsonapi/issues/2943176 lands.
    $expected = [
      'errors' => [
        [
          'title' => 'Forbidden',
          'status' => 403,
          'detail' => "The current user is not allowed to GET the selected resource." . (strlen($reason) ? ' ' . $reason : ''),
          'links' => [
            'info' => HttpExceptionNormalizer::getInfoUrl(403),
          ],
          'code' => 0,
          'source' => [
            'pointer' => '/data',
          ],
        ],
      ],
    ];
    $this->assertResourceResponse(403, Json::encode($expected), $response);
    /* $this->assertResourceErrorResponse(403, "The current user is not allowed to GET the selected resource." . (strlen($reason) ? ' ' . $reason : ''), $response, '/data'); */
    // @todo Uncomment in https://www.drupal.org/project/jsonapi/issues/2929428.
    /* $this->assertResourceResponse(403, Json::encode($expected), $response, $expected_403_cacheability->getCacheTags(), $expected_403_cacheability->getCacheContexts(), FALSE, 'MISS'); */
    $this->assertArrayNotHasKey('Link', $response->getHeaders());

    $this->setUpAuthorization('GET');

    // 200 for well-formed HEAD request.
    $response = $this->request('HEAD', $url, $request_options);
    $this->assertResourceResponse(200, '', $response, $this->getExpectedCacheTags(), $this->getExpectedCacheContexts(), FALSE, 'MISS');
    $head_headers = $response->getHeaders();

    // 200 for well-formed GET request. Page Cache hit because of HEAD request.
    // Same for Dynamic Page Cache hit.
    $response = $this->request('GET', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response, $this->getExpectedCacheTags(), $this->getExpectedCacheContexts(), FALSE, 'HIT');
    // Assert that Dynamic Page Cache did not store a ResourceResponse object,
    // which needs serialization after every cache hit. Instead, it should
    // contain a flattened response. Otherwise performance suffers.
    // @see \Drupal\jsonapi\EventSubscriber\ResourceResponseSubscriber::flattenResponse()
    $cache_items = $this->container->get('database')
      ->query("SELECT cid, data FROM {cache_dynamic_page_cache} WHERE cid LIKE :pattern", [
        ':pattern' => '%[route]=jsonapi.%',
      ])
      ->fetchAllAssoc('cid');
    $this->assertTrue(count($cache_items) >= 2);
    $found_cache_redirect = FALSE;
    $found_cached_200_response = FALSE;
    $other_cached_responses_are_4xx = TRUE;
    foreach ($cache_items as $cid => $cache_item) {
      $cached_data = unserialize($cache_item->data);
      if (!isset($cached_data['#cache_redirect'])) {
        $cached_response = $cached_data['#response'];
        if ($cached_response->getStatusCode() === 200) {
          $found_cached_200_response = TRUE;
        }
        elseif (!$cached_response->isClientError()) {
          $other_cached_responses_are_4xx = FALSE;
        }
        $this->assertNotInstanceOf(ResourceResponse::class, $cached_response);
        $this->assertInstanceOf(CacheableResponseInterface::class, $cached_response);
      }
      else {
        $found_cache_redirect = TRUE;
      }
    }
    $this->assertTrue($found_cache_redirect);
    $this->assertTrue($found_cached_200_response);
    $this->assertTrue($other_cached_responses_are_4xx);

    // Sort the serialization data first so we can do an identical comparison
    // for the keys with the array order the same (it needs to match with
    // identical comparison).
    $expected = $this->getExpectedDocument();
    static::recursiveKsort($expected);
    $actual = Json::decode((string) $response->getBody());
    static::recursiveKsort($actual);
    $this->assertSame($expected, $actual);

    // Not only assert the normalization, also assert deserialization of the
    // response results in the expected object.
    // @todo Uncomment this in https://www.drupal.org/project/jsonapi/issues/2942561#comment-12472704.
    // @codingStandardsIgnoreStart
    /*
    $unserialized = $this->serializer->deserialize((string) $response->getBody(), get_class($this->entity), 'api_json', [
      'target_entity' => static::$entityTypeId,
      'resource_type' => $this->container->get('jsonapi.resource_type.repository')->getByTypeName(static::$resourceTypeName),
    ]);
    $this->assertSame($unserialized->uuid(), $this->entity->uuid());
    */
    // @codingStandardsIgnoreEnd
    $get_headers = $response->getHeaders();

    // Verify that the GET and HEAD responses are the same. The only difference
    // is that there's no body. For this reason the 'Transfer-Encoding' and
    // 'Vary' headers are also added to the list of headers to ignore, as they
    // may be added to GET requests, depending on web server configuration. They
    // are usually 'Transfer-Encoding: chunked' and 'Vary: Accept-Encoding'.
    $ignored_headers = [
      'Date',
      'Content-Length',
      'X-Drupal-Cache',
      'X-Drupal-Dynamic-Cache',
      'Transfer-Encoding',
      'Vary',
    ];
    $header_cleaner = function ($headers) use ($ignored_headers) {
      foreach ($headers as $header => $value) {
        if (strpos($header, 'X-Drupal-Assertion-') === 0 || in_array($header, $ignored_headers)) {
          unset($headers[$header]);
        }
      }
      return $headers;
    };
    $get_headers = $header_cleaner($get_headers);
    $head_headers = $header_cleaner($head_headers);
    $this->assertSame($get_headers, $head_headers);

    // @todo Uncomment this in https://www.drupal.org/project/jsonapi/issues/2929932.
    // @codingStandardsIgnoreStart
    /*
    // BC: serialization_update_8401().
    // Only run this for fieldable entities. It doesn't make sense for config
    // entities as config values always use the raw values (as per the config
    // schema), returned directly from the ConfigEntityNormalizer, which
    // doesn't deal with fields individually.
    if ($this->entity instanceof FieldableEntityInterface) {
      // Test the BC settings for timestamp values.
      $this->config('serialization.settings')->set('bc_timestamp_normalizer_unix', TRUE)->save(TRUE);
      // Rebuild the container so new config is reflected in the addition of the
      // TimestampItemNormalizer.
      $this->rebuildAll();

      $response = $this->request('GET', $url, $request_options);
      $this->assertResourceResponse(200, FALSE, $response, $this->getExpectedCacheTags(), $this->getExpectedCacheContexts(), static::$auth ? FALSE : 'MISS', 'MISS');

      // This ensures the BC layer for bc_timestamp_normalizer_unix works as
      // expected. This method should be using
      // ::formatExpectedTimestampValue() to generate the timestamp value. This
      // will take into account the above config setting.
      $expected = $this->getExpectedNormalizedEntity();
      // Config entities are not affected.
      // @see \Drupal\serialization\Normalizer\ConfigEntityNormalizer::normalize()
      static::recursiveKsort($expected);
      $actual = Json::decode((string) $response->getBody());
      static::recursiveKsort($actual);
      $this->assertSame($expected, $actual);

      // Reset the config value and rebuild.
      $this->config('serialization.settings')->set('bc_timestamp_normalizer_unix', FALSE)->save(TRUE);
      $this->rebuildAll();
    }
    */
    // @codingStandardsIgnoreEnd

    // DX: 404 when GETting non-existing entity.
    $random_uuid = \Drupal::service('uuid')->generate();
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), [static::$entityTypeId => $random_uuid]);
    $response = $this->request('GET', $url, $request_options);
    $message_url = clone $url;
    $path = str_replace($random_uuid, '{' . static::$entityTypeId . '}', $message_url->setAbsolute()->setOptions(['base_url' => '', 'query' => []])->toString());
    $message = 'The "' . static::$entityTypeId . '" parameter was not converted for the path "' . $path . '" (route name: "jsonapi.' . static::$resourceTypeName . '.individual")';
    $this->assertResourceErrorResponse(404, $message, $response);

    // DX: when Accept request header is missing, still 404, but HTML response.
    unset($request_options[RequestOptions::HEADERS]['Accept']);
    $response = $this->request('GET', $url, $request_options);
    $this->assertSame(404, $response->getStatusCode());
    $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
  }

  /**
   * Tests POSTing an individual resource, plus edge cases to ensure good DX.
   */
  public function testPostIndividual() {
    // @todo Remove this in https://www.drupal.org/node/2300677.
    if ($this->entity instanceof ConfigEntityInterface) {
      $this->assertTrue(TRUE, 'POSTing config entities is not yet supported.');
      return;
    }

    // Try with all of the following request bodies.
    $unparseable_request_body = '!{>}<';
    $parseable_valid_request_body = Json::encode($this->getPostDocument());
    /* $parseable_valid_request_body_2 = Json::encode($this->getNormalizedPostEntity()); */
    $parseable_invalid_request_body_missing_type = Json::encode($this->removeResourceTypeFromDocument($this->getPostDocument(), 'type'));
    $parseable_invalid_request_body = Json::encode($this->makeLabelFieldNormalizationInvalid($this->getPostDocument()));
    $parseable_invalid_request_body_2 = Json::encode(NestedArray::mergeDeep(['data' => ['id' => $this->randomMachineName(129)]], $this->getPostDocument()));
    /* $parseable_invalid_request_body_3 = Json::encode(NestedArray::mergeDeep(['data' => ['attributes' => ['field_rest_test' => $this->randomString()]]], $this->getNormalizedPostEntity())); */

    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    $url = Url::fromRoute(sprintf('jsonapi.%s.collection', static::$resourceTypeName));
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // @todo Uncomment in https://www.drupal.org/project/jsonapi/issues/2943170.
    // @codingStandardsIgnoreStart
    /*
    // DX: 415 when no Content-Type request header. HTML response because
    // missing ?_format query string.
    $response = $this->request('POST', $url, $request_options);
    $this->assertSame(415, $response->getStatusCode());
    $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
    $this->assertContains('A client error happened', (string) $response->getBody());

    $url->setOption('query', ['_format' => 'api_json']);

    // DX: 415 when no Content-Type request header.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(415, '…', 'No "Content-Type" request header specified', $response);

    $request_options[RequestOptions::HEADERS]['Content-Type'] = '';

    // DX: 400 when no request body.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'No entity content received.', $response);
*/
    // @codingStandardsIgnoreEnd

    $request_options[RequestOptions::BODY] = $unparseable_request_body;

    // DX: 400 when unparseable request body.
    $response = $this->request('POST', $url, $request_options);
    // @todo Uncomment in https://www.drupal.org/project/jsonapi/issues/2943165.
    /* $this->assertResourceErrorResponse(400, 'Bad Request', 'Syntax error', $response); */

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body;

    // DX: 403 when unauthorized.
    $response = $this->request('POST', $url, $request_options);
    $reason = $this->getExpectedUnauthorizedAccessMessage('POST');
    // @todo Remove $expected + assertResourceResponse() in favor of the commented line below once https://www.drupal.org/project/jsonapi/issues/2943176 lands.
    $expected = [
      'errors' => [
        [
          'title' => 'Forbidden',
          'status' => 403,
          // @todo Why is the reason missing here?
          'detail' => "The current user is not allowed to POST the selected resource." . (strlen($reason) ? ' ' . $reason : ''),
          'links' => [
            'info' => HttpExceptionNormalizer::getInfoUrl(403),
          ],
          'code' => 0,
          'source' => [
            'pointer' => '/data',
          ],
        ],
      ],
    ];
    $this->assertResourceResponse(403, Json::encode($expected), $response);
    /* $this->assertResourceErrorResponse(403, "The current user is not allowed to POST the selected resource." . (strlen($reason) ? ' ' . $reason : ''), $response, '/data'); */

    $this->setUpAuthorization('POST');

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_missing_type;

    // DX: 400 when invalid JSON API request body.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'Resource object must include a "type".', $response);

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body;

    // DX: 422 when invalid entity: multiple values sent for single-value field.
    $response = $this->request('POST', $url, $request_options);
    $label_field = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName;
    $label_field_capitalized = $this->entity->getFieldDefinition($label_field)->getLabel();
    // @todo Remove $expected + assertResourceResponse() in favor of the commented line below once https://www.drupal.org/project/jsonapi/issues/2943176 lands.
    $expected = [
      'errors' => [
        [
          'title' => 'Unprocessable Entity',
          'status' => 422,
          'detail' => "$label_field: $label_field_capitalized: this field cannot hold more than 1 values.",
          'code' => 0,
          'source' => [
            'pointer' => '/data/attributes/' . $label_field,
          ],
        ],
      ],
    ];
    $this->assertResourceResponse(422, Json::encode($expected), $response);
    /* $this->assertResourceErrorResponse(422, "$label_field: $label_field_capitalized: this field cannot hold more than 1 values.", $response, '/data/attributes/' . $label_field); */

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_2;

    // @todo Uncomment when https://www.drupal.org/project/jsonapi/issues/2934386 lands.
    // DX: 403 when invalid entity: UUID field too long.
    // @todo Fix this in https://www.drupal.org/node/2149851.
    if ($this->entity->getEntityType()->hasKey('uuid')) {
      $response = $this->request('POST', $url, $request_options);
      // @todo Remove $expected + assertResourceResponse() in favor of the commented line below once https://www.drupal.org/project/jsonapi/issues/2943176 lands.
      $expected = [
        'errors' => [
          [
            'title' => 'Forbidden',
            'status' => 403,
            'detail' => "IDs should be properly generated and formatted UUIDs as described in RFC 4122.",
            'links' => [
              'info' => HttpExceptionNormalizer::getInfoUrl(403),
            ],
            'code' => 0,
            'source' => [
              'pointer' => '/data/id',
            ],
          ],
        ],
      ];
      $this->assertResourceResponse(403, Json::encode($expected), $response);
      /* $this->assertResourceErrorResponse(403, "IDs should be properly generated and formatted UUIDs as described in RFC 4122.", $response, '/data/id'); */
    }

    // @codingStandardsIgnoreStart
//    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_3;
//
//    // DX: 403 when entity contains field without 'edit' access.
//    $response = $this->request('POST', $url, $request_options);
//    $this->assertResourceErrorResponse(403, "Access denied on creating field 'field_rest_test'.", $response);
    // @codingStandardsIgnoreEnd

    $request_options[RequestOptions::BODY] = $parseable_valid_request_body;

    // @todo Uncomment when https://www.drupal.org/project/jsonapi/issues/2934149 lands.
    // @codingStandardsIgnoreStart
    /*
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'text/xml';

    // DX: 415 when request body in existing but not allowed format.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceErrorResponse(415, 'No route found that matches "Content-Type: text/xml"', $response);
    */
    // @codingStandardsIgnoreEnd

    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';

    // 201 for well-formed request.
    $response = $this->request('POST', $url, $request_options);
    $this->assertResourceResponse(201, FALSE, $response);
    // @todo Remove this logic to extract a UUID from the response in https://www.drupal.org/project/jsonapi/issues/2944977
    if (get_class($this->entityStorage) !== ContentEntityNullStorage::class) {
      $uuid = $this->entityStorage->load(static::$firstCreatedEntityId)->uuid();
    }
    else {
      $r = Json::decode((string) $response->getBody());
      $uuid = NestedArray::getValue($r, ['data', 'id']);
    }
    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $location = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), [static::$entityTypeId => $uuid])->setAbsolute(TRUE)->toString();
    /* $location = $this->entityStorage->load(static::$firstCreatedEntityId)->toUrl('jsonapi')->setAbsolute(TRUE)->toString(); */
    $this->assertSame([$location], $response->getHeader('Location'));
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    // If the entity is stored, perform extra checks.
    if (get_class($this->entityStorage) !== ContentEntityNullStorage::class) {
      // Assert that the entity was indeed created, and that the response body
      // contains the serialized created entity.
      $created_entity = $this->entityStorage->loadUnchanged(static::$firstCreatedEntityId);
      $created_entity_document = $this->entityToJsonApi->normalize($created_entity);
      // @todo Remove this if-test in https://www.drupal.org/node/2543726: execute
      // its body unconditionally.
      if (static::$entityTypeId !== 'taxonomy_term') {
        $decoded_response_body = Json::decode((string) $response->getBody());
        // @todo Remove the two lines below once https://www.drupal.org/project/jsonapi/issues/2925043 lands.
        unset($created_entity_document['links']);
        unset($decoded_response_body['links']);
        $this->assertSame($created_entity_document, $decoded_response_body);
      }
      // Assert that the entity was indeed created using the POSTed values.
      foreach ($this->getPostDocument()['data']['attributes'] as $field_name => $field_normalization) {
        // If the value is an array of properties, only verify that the sent
        // properties are present, the server could be computing additional
        // properties.
        if (is_array($field_normalization)) {
          $this->assertArraySubset($field_normalization, $created_entity_document['data']['attributes'][$field_name]);
        }
        else {
          $this->assertSame($field_normalization, $created_entity_document['data']['attributes'][$field_name]);
        }
      }
      if (isset($this->getPostDocument()['data']['relationships'])) {
        foreach ($this->getPostDocument()['data']['relationships'] as $field_name => $relationship_field_normalization) {
          // POSTing relationships: 'data' is required, 'links' is optional.
          $this->assertSame($relationship_field_normalization, array_diff_key($created_entity_document['data']['relationships'][$field_name], ['links' => TRUE]));
        }
      }
    }
  }

  /**
   * Tests PATCHing an individual resource, plus edge cases to ensure good DX.
   */
  public function testPatchIndividual() {
    // @todo Remove this in https://www.drupal.org/node/2300677.
    if ($this->entity instanceof ConfigEntityInterface) {
      $this->assertTrue(TRUE, 'PATCHing config entities is not yet supported.');
      return;
    }

    // Try with all of the following request bodies.
    $unparseable_request_body = '!{>}<';
    $parseable_valid_request_body = Json::encode($this->getPatchDocument());
    /* $parseable_valid_request_body_2 = Json::encode($this->getNormalizedPatchEntity()); */
    $parseable_invalid_request_body = Json::encode($this->makeLabelFieldNormalizationInvalid($this->getPatchDocument()));
    $parseable_invalid_request_body_2 = Json::encode(NestedArray::mergeDeep(['data' => ['attributes' => ['field_rest_test' => $this->randomString()]]], $this->getPatchDocument()));
    // The 'field_rest_test' field does not allow 'view' access, so does not end
    // up in the JSON API document. Even when we explicitly add it to the JSON
    // API document that we send in a PATCH request, it is considered invalid.
    $parseable_invalid_request_body_3 = Json::encode(NestedArray::mergeDeep(['data' => ['attributes' => ['field_rest_test' => $this->entity->get('field_rest_test')->getValue()]]], $this->getPatchDocument()));

    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), [static::$entityTypeId => $this->entity->uuid()]);
    /* $url = $this->entity->toUrl('jsonapi'); */
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // @todo Uncomment in https://www.drupal.org/project/jsonapi/issues/2943170.
    // @codingStandardsIgnoreStart
    /*
    // DX: 415 when no Content-Type request header.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertSame(415, $response->getStatusCode());
    $this->assertSame(['text/html; charset=UTF-8'], $response->getHeader('Content-Type'));
    $this->assertContains('A client error happened', (string) $response->getBody());

    $url->setOption('query', ['_format' => static::$format]);

    // DX: 415 when no Content-Type request header.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(415, 'No "Content-Type" request header specified', $response);

    $request_options[RequestOptions::HEADERS]['Content-Type'] = static::$mimeType;

    // DX: 400 when no request body.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(400, 'No entity content received.', $response);
*/
    // @codingStandardsIgnoreEnd

    $request_options[RequestOptions::BODY] = $unparseable_request_body;

    // DX: 400 when unparseable request body.
    $response = $this->request('PATCH', $url, $request_options);
    // @todo Uncomment in https://www.drupal.org/project/jsonapi/issues/2943165.
    /* $this->assertResourceErrorResponse(400, 'Syntax error', $response); */

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body;

    // DX: 403 when unauthorized.
    $response = $this->request('PATCH', $url, $request_options);
    $reason = $this->getExpectedUnauthorizedAccessMessage('PATCH');
    // @todo Remove $expected + assertResourceResponse() in favor of the commented line below once https://www.drupal.org/project/jsonapi/issues/2943176 lands.
    $expected = [
      'errors' => [
        [
          'title' => 'Forbidden',
          'status' => 403,
          'detail' => "The current user is not allowed to PATCH the selected resource." . (strlen($reason) ? ' ' . $reason : ''),
          'links' => [
            'info' => HttpExceptionNormalizer::getInfoUrl(403),
          ],
          'code' => 0,
          'source' => [
            'pointer' => '/data',
          ],
        ],
      ],
    ];
    $this->assertResourceResponse(403, Json::encode($expected), $response);
    /* $this->assertResourceErrorResponse(403, "The current user is not allowed to PATCH the selected resource." . (strlen($reason) ? ' ' . $reason : ''), $response, '/data'); */

    $this->setUpAuthorization('PATCH');

    // DX: 422 when invalid entity: multiple values sent for single-value field.
    $response = $this->request('PATCH', $url, $request_options);
    $label_field = $this->entity->getEntityType()->hasKey('label') ? $this->entity->getEntityType()->getKey('label') : static::$labelFieldName;
    $label_field_capitalized = $this->entity->getFieldDefinition($label_field)->getLabel();
    // @todo Remove $expected + assertResourceResponse() in favor of the commented line below once https://www.drupal.org/project/jsonapi/issues/2943176 lands.
    $expected = [
      'errors' => [
        [
          'title' => 'Unprocessable Entity',
          'status' => 422,
          'detail' => "$label_field: $label_field_capitalized: this field cannot hold more than 1 values.",
          'code' => 0,
          'source' => [
            'pointer' => '/data/attributes/' . $label_field,
          ],
        ],
      ],
    ];
    $this->assertResourceResponse(422, Json::encode($expected), $response);
    /* $this->assertResourceErrorResponse(422, "$label_field: $label_field_capitalized: this field cannot hold more than 1 values.", $response, '/data/attributes/' . $label_field); */

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_2;

    // DX: 403 when entity contains field without 'edit' access.
    $response = $this->request('PATCH', $url, $request_options);
    // @todo Remove $expected + assertResourceResponse() in favor of the commented line below once https://www.drupal.org/project/jsonapi/issues/2943176 lands.
    $expected = [
      'errors' => [
        [
          'title' => 'Forbidden',
          'status' => 403,
          'detail' => "The current user is not allowed to PATCH the selected field (field_rest_test).",
          'links' => [
            'info' => HttpExceptionNormalizer::getInfoUrl(403),
          ],
          'code' => 0,
          'source' => [
            'pointer' => '/data/attributes/field_rest_test',
          ],
        ],
      ],
    ];
    $this->assertResourceResponse(403, Json::encode($expected), $response);
    /* $this->assertResourceErrorResponse(403, "The current user is not allowed to PATCH the selected field (field_rest_test).", $response, '/data/attributes/field_rest_test'); */

    $request_options[RequestOptions::BODY] = $parseable_invalid_request_body_3;

    // DX: 403 when entity contains field without 'edit' nor 'view' access, even
    // when the value for that field matches the current value. This is allowed
    // in principle, but leads to information disclosure.
    $response = $this->request('PATCH', $url, $request_options);
    // @todo Remove $expected + assertResourceResponse() in favor of the commented line below once https://www.drupal.org/project/jsonapi/issues/2943176 lands.
    $expected = [
      'errors' => [
        [
          'title' => 'Forbidden',
          'status' => 403,
          'detail' => "The current user is not allowed to PATCH the selected field (field_rest_test).",
          'links' => [
            'info' => HttpExceptionNormalizer::getInfoUrl(403),
          ],
          'code' => 0,
          'source' => [
            'pointer' => '/data/attributes/field_rest_test',
          ],
        ],
      ],
    ];
    $this->assertResourceResponse(403, Json::encode($expected), $response);
    /* $this->assertResourceErrorResponse(403, "The current user is not allowed to PATCH the selected field (field_rest_test).", $response, '/data/attributes/field_rest_test'); */

    // DX: 403 when sending PATCH request with updated read-only fields.
    list($modified_entity, $original_values) = static::getModifiedEntityForPatchTesting($this->entity);
    // Send PATCH request by serializing the modified entity, assert the error
    // response, change the modified entity field that caused the error response
    // back to its original value, repeat.
    foreach (static::$patchProtectedFieldNames as $patch_protected_field_name => $reason) {
      $request_options[RequestOptions::BODY] = $this->entityToJsonApi->serialize($modified_entity);
      $response = $this->request('PATCH', $url, $request_options);
      // @todo Remove $expected + assertResourceResponse() in favor of the commented line below once https://www.drupal.org/project/jsonapi/issues/2943176 lands.
      $expected = [
        'errors' => [
          [
            'title' => 'Forbidden',
            'status' => 403,
            // @todo Remove this line in favor of the commented line once https://www.drupal.org/project/drupal/issues/2938053 lands.
            'detail' => "The current user is not allowed to PATCH the selected field (" . $patch_protected_field_name . ").",
            /* 'detail' => "The current user is not allowed to PATCH the selected field (" . $patch_protected_field_name . ")." . ($reason !== NULL ? ' ' . $reason : ''), */
            'links' => [
              'info' => HttpExceptionNormalizer::getInfoUrl(403),
            ],
            'code' => 0,
            'source' => [
              'pointer' => '/data/attributes/' . $patch_protected_field_name,
            ],
          ],
        ],
      ];
      $this->assertResourceResponse(403, Json::encode($expected), $response);
      /* $this->assertResourceErrorResponse(403, "The current user is not allowed to PATCH the selected field (" . $patch_protected_field_name . ")." . ($reason !== NULL ? ' ' . $reason : ''), $response, '/data/attributes/' . $patch_protected_field_name); */
      $modified_entity->get($patch_protected_field_name)->setValue($original_values[$patch_protected_field_name]);
    }

    // 200 for well-formed PATCH request that sends all fields (even including
    // read-only ones, but with unchanged values).
    $valid_request_body = NestedArray::mergeDeep($this->entityToJsonApi->normalize($this->entity), $this->getPatchDocument());
    // @todo Remove this foreach in https://www.drupal.org/project/jsonapi/issues/2939810.
    foreach (array_keys(static::$patchProtectedFieldNames) as $field_name) {
      unset($valid_request_body['data']['attributes'][$field_name]);
    }
    $request_options[RequestOptions::BODY] = Json::encode($valid_request_body);
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);

    $request_options[RequestOptions::BODY] = $parseable_valid_request_body;

    // @todo Uncomment when https://www.drupal.org/project/jsonapi/issues/2934149 lands.
    // @codingStandardsIgnoreStart
    /*
    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'text/xml';

    // DX: 415 when request body in existing but not allowed format.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceErrorResponse(415, 'No route found that matches "Content-Type: text/xml"', $response);
    */
    // @codingStandardsIgnoreEnd

    $request_options[RequestOptions::HEADERS]['Content-Type'] = 'application/vnd.api+json';

    // 200 for well-formed request.
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $this->assertFalse($response->hasHeader('X-Drupal-Cache'));
    // Assert that the entity was indeed updated, and that the response body
    // contains the serialized updated entity.
    $updated_entity = $this->entityStorage->loadUnchanged($this->entity->id());
    $updated_entity_document = $this->entityToJsonApi->normalize($updated_entity);
    $this->assertSame($updated_entity_document, Json::decode((string) $response->getBody()));
    // Assert that the entity was indeed created using the PATCHed values.
    foreach ($this->getPatchDocument() as $field_name => $field_normalization) {
      // Some top-level keys in the normalization may not be fields on the
      // entity (for example '_links' and '_embedded' in the HAL normalization).
      if ($updated_entity->hasField($field_name)) {
        // Subset, not same, because we can e.g. send just the target_id for the
        // bundle in a PATCH request; the response will include more properties.
        $this->assertArraySubset(static::castToString($field_normalization), $updated_entity->get($field_name)->getValue(), TRUE);
      }
    }

    // Ensure that fields do not get deleted if they're not present in the PATCH
    // request. Test this using the configurable field that we added, but which
    // is not sent in the PATCH request.
    $this->assertSame('All the faith he had had had had no effect on the outcome of his life.', $updated_entity->get('field_rest_test')->value);

    // @todo Remove this when JSON API requires Drupal 8.5 or newer.
    if (floatval(\Drupal::VERSION) < 8.5) {
      return;
    }

    // Multi-value field: remove item 0. Then item 1 becomes item 0.
    $doc_multi_value_tests = $this->getPatchDocument();
    $doc_multi_value_tests['data']['attributes']['field_rest_test_multivalue'] = $this->entity->get('field_rest_test_multivalue')->getValue();
    $doc_remove_item = $doc_multi_value_tests;
    unset($doc_remove_item['data']['attributes']['field_rest_test_multivalue'][0]);
    $request_options[RequestOptions::BODY] = Json::encode($doc_remove_item, 'api_json');
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $this->assertSame([0 => ['value' => 'Two']], $this->entityStorage->loadUnchanged($this->entity->id())->get('field_rest_test_multivalue')->getValue());

    // Multi-value field: add one item before the existing one, and one after.
    $doc_add_items = $doc_multi_value_tests;
    $doc_add_items['data']['attributes']['field_rest_test_multivalue'][2] = ['value' => 'Three'];
    $request_options[RequestOptions::BODY] = Json::encode($doc_add_items);
    $response = $this->request('PATCH', $url, $request_options);
    $this->assertResourceResponse(200, FALSE, $response);
    $expected = [
      0 => ['value' => 'One'],
      1 => ['value' => 'Two'],
      2 => ['value' => 'Three'],
    ];
    $this->assertSame($expected, $this->entityStorage->loadUnchanged($this->entity->id())->get('field_rest_test_multivalue')->getValue());
  }

  /**
   * Tests DELETEing an individual resource, plus edge cases to ensure good DX.
   */
  public function testDeleteIndividual() {
    // @todo Remove this in https://www.drupal.org/node/2300677.
    if ($this->entity instanceof ConfigEntityInterface) {
      $this->assertTrue(TRUE, 'DELETEing config entities is not yet supported.');
      return;
    }

    // The URL and Guzzle request options that will be used in this test. The
    // request options will be modified/expanded throughout this test:
    // - to first test all mistakes a developer might make, and assert that the
    //   error responses provide a good DX
    // - to eventually result in a well-formed request that succeeds.
    // @todo Remove line below in favor of commented line in https://www.drupal.org/project/jsonapi/issues/2878463.
    $url = Url::fromRoute(sprintf('jsonapi.%s.individual', static::$resourceTypeName), [static::$entityTypeId => $this->entity->uuid()]);
    /* $url = $this->entity->toUrl('jsonapi'); */
    $request_options = [];
    $request_options[RequestOptions::HEADERS]['Accept'] = 'application/vnd.api+json';
    $request_options = NestedArray::mergeDeep($request_options, $this->getAuthenticationRequestOptions());

    // DX: 403 when unauthorized.
    $response = $this->request('DELETE', $url, $request_options);
    $reason = $this->getExpectedUnauthorizedAccessMessage('DELETE');
    // @todo Remove $expected + assertResourceResponse() in favor of the commented line below once https://www.drupal.org/project/jsonapi/issues/2943176 lands.
    $expected = [
      'errors' => [
        [
          'title' => 'Forbidden',
          'status' => 403,
          'detail' => "The current user is not allowed to DELETE the selected resource." . (strlen($reason) ? ' ' . $reason : ''),
          'links' => [
            'info' => HttpExceptionNormalizer::getInfoUrl(403),
          ],
          'code' => 0,
          'source' => [
            'pointer' => '/data',
          ],
        ],
      ],
    ];
    $this->assertResourceResponse(403, Json::encode($expected), $response);
    /* $this->assertResourceErrorResponse(403, "The current user is not allowed to DELETE the selected resource." . (strlen($reason) ? ' ' . $reason : ''), $response, '/data'); */

    $this->setUpAuthorization('DELETE');

    // 204 for well-formed request.
    $response = $this->request('DELETE', $url, $request_options);
    $this->assertResourceResponse(204, '', $response);
  }

  /**
   * Transforms a normalization: casts all non-string types to strings.
   *
   * @param array $normalization
   *   A normalization to transform.
   *
   * @return array
   *   The transformed normalization.
   */
  protected static function castToString(array $normalization) {
    foreach ($normalization as $key => $value) {
      if (is_bool($value)) {
        $normalization[$key] = (string) (int) $value;
      }
      elseif (is_int($value) || is_float($value)) {
        $normalization[$key] = (string) $value;
      }
      elseif (is_array($value)) {
        $normalization[$key] = static::castToString($value);
      }
    }
    return $normalization;
  }

  /**
   * Recursively sorts an array by key.
   *
   * @param array $array
   *   An array to sort.
   */
  protected static function recursiveKsort(array &$array) {
    // First, sort the main array.
    ksort($array);

    // Then check for child arrays.
    foreach ($array as $key => &$value) {
      if (is_array($value)) {
        static::recursiveKsort($value);
      }
    }
  }

  /**
   * Returns Guzzle request options for authentication.
   *
   * @return array
   *   Guzzle request options to use for authentication.
   *
   * @see \GuzzleHttp\ClientInterface::request()
   */
  protected function getAuthenticationRequestOptions() {
    return [
      'headers' => [
        'Authorization' => 'Basic ' . base64_encode($this->account->name->value . ':' . $this->account->passRaw),
      ],
    ];
  }

  /**
   * Clones the given entity and modifies all PATCH-protected fields.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity being tested and to modify.
   *
   * @return array
   *   Contains two items:
   *   1. The modified entity object.
   *   2. The original field values, keyed by field name.
   *
   * @internal
   */
  protected static function getModifiedEntityForPatchTesting(EntityInterface $entity) {
    $modified_entity = clone $entity;
    $original_values = [];
    foreach (array_keys(static::$patchProtectedFieldNames) as $field_name) {
      $field = $modified_entity->get($field_name);
      $original_values[$field_name] = $field->getValue();
      switch ($field->getItemDefinition()->getClass()) {
        case EntityReferenceItem::class:
          // EntityReferenceItem::generateSampleValue() picks one of the last 50
          // entities of the supported type & bundle. We don't care if the value
          // is valid, we only care that it's different.
          $field->setValue(['target_id' => 99999]);
          break;

        case BooleanItem::class:
          // BooleanItem::generateSampleValue() picks either 0 or 1. So a 50%
          // chance of not picking a different value.
          $field->value = ((int) $field->value) === 1 ? '0' : '1';
          break;

        case PathItem::class:
          // PathItem::generateSampleValue() doesn't set a PID, which causes
          // PathItem::postSave() to fail. Keep the PID (and other properties),
          // just modify the alias.
          $field->alias = str_replace(' ', '-', strtolower((new Random())->sentences(3)));
          break;

        default:
          $original_field = clone $field;
          while ($field->equals($original_field)) {
            $field->generateSampleItems();
          }
          break;
      }
    }

    return [$modified_entity, $original_values];
  }

}