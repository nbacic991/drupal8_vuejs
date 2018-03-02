<?php

namespace Drupal\Tests\jsonapi\Functional;

use Drupal\Core\Url;
use Drupal\workflows\Entity\Workflow;

/**
 * JSON API integration test for the "Workflow" config entity type.
 *
 * @group jsonapi
 */
class WorkflowTest extends ResourceTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['workflows', 'workflow_type_test'];

  /**
   * {@inheritdoc}
   */
  protected static $entityTypeId = 'workflow';

  /**
   * {@inheritdoc}
   */
  protected static $resourceTypeName = 'workflow--workflow';

  /**
   * {@inheritdoc}
   *
   * @var \Drupal\shortcut\ShortcutSetInterface
   */
  protected $entity;

  /**
   * {@inheritdoc}
   */
  protected function setUpAuthorization($method) {
    $this->grantPermissionsToTestedRole(['administer workflows']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity() {
    $workflow = Workflow::create([
      'id' => 'rest_workflow',
      'label' => 'REST Worklow',
      'type' => 'workflow_type_complex_test',
    ]);
    $workflow
      ->getTypePlugin()
      ->addState('draft', 'Draft')
      ->addState('published', 'Published');
    $configuration = $workflow->getTypePlugin()->getConfiguration();
    $configuration['example_setting'] = 'foo';
    $configuration['states']['draft']['extra'] = 'bar';
    $workflow->getTypePlugin()->setConfiguration($configuration);
    $workflow->save();
    return $workflow;
  }

  /**
   * {@inheritdoc}
   */
  protected function getExpectedDocument() {
    $self_url = Url::fromUri('base:/jsonapi/workflow/workflow/' . $this->entity->uuid())->setAbsolute()->toString(TRUE)->getGeneratedUrl();
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
        'type' => 'workflow--workflow',
        'links' => [
          'self' => $self_url,
        ],
        'attributes' => [
          'dependencies' => [
            // @todo Remove the first line in favor of the 3 commented lines in https://www.drupal.org/project/jsonapi/issues/2942979
            // @codingStandardsIgnoreStart
            'workflow_type_test',
//            'module' => [
//              'workflow_type_test',
//            ],
            // @codingStandardsIgnoreEnd
          ],
          'id' => 'rest_workflow',
          'label' => 'REST Worklow',
          'langcode' => 'en',
          'status' => TRUE,
          'type' => 'workflow_type_complex_test',
          'type_settings' => [
            'states' => [
              'draft' => [
                'extra' => 'bar',
                'label' => 'Draft',
                'weight' => 0,
              ],
              'published' => [
                'label' => 'Published',
                'weight' => 1,
              ],
            ],
            'transitions' => [],
            'example_setting' => 'foo',
          ],
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

}