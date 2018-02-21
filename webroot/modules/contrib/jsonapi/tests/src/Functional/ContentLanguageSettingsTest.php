<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Url;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\node\Entity\NodeType;

/**
 * JSON API integration test for "ContentLanguageSettings" config entity type.
 *
 * @group jsonapi
 */
class ContentLanguageSettingsTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['language', 'node'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'language_content_settings';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'language_content_settings--language_content_settings';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\language\ContentLanguageSettingsInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer languages']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    // Create a "Camelids" node type.
    $camelids = NodeType::create([
      'name' => 'Camelids',
      'type' => 'camelids',
    ]);
    $camelids->save();

    $entity = ContentLanguageSettings::create([
      'target_entity_type_id' => 'node',
      'target_bundle' => 'camelids',
    ]);
    $entity->setDefaultLangcode('site_default')
      ->save();

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/language_content_settings/language_content_settings/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
    return [
      'jsonapi' => [
        'meta' => [
          'links' => [
            'self' => 'http://jsonapi.org/format/1.0/',
          ],
        ],
        'version' => '1.0',
      ],
      'links' => [
        'self' => $self_url,
      ],
      'data' => [
        'id' => $this->entity->uuid(),
        'type' => 'language_content_settings--language_content_settings',
        'links' => [
          'self' => $self_url,
        ],
        'attributes' => [
          'default_langcode' => 'site_default',
          'dependencies' => [
            // @todo Remove this first line in favor of the 3 commented lines in https://www.drupal.org/project/jsonapi/issues/2942979
            // @codingStandardsIgnoreStart
            'node.type.camelids',
//            'config' => [
//              'node.type.camelids',
//            ],
            // @codingStandardsIgnoreEnd
          ],
          'id' => 'node.camelids',
          'langcode' => 'en',
          'language_alterable' => FALSE,
          'status' => TRUE,
          'target_bundle' => 'camelids',
          'target_entity_type_id' => 'node',
          'uuid' => $this->entity->uuid(),
        ],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  protected function getPostDocument() {
    // @todo Update in https://www.drupal.org/node/2300677.
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedCacheContexts() {
    // @todo Uncomment first 4 lines, remove last line in https://www.drupal.org/project/jsonapi/issues/2940342.
    // @codingStandardsIgnoreStart
//    return [
//      'languages:language_interface',
//      'user.permissions',
//    ];
    // @codingStandardsIgnoreEnd
    return Cache::mergeContexts(parent::getExpectedCacheContexts(), ['languages:language_interface']);
  }

}
