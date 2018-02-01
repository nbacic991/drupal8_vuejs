<?php

namespace Drupal\menu_entity_index\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\menu_entity_index\TrackerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a configuration form for administrative settings.
 *
 * @package Drupal\menu_entity_index\Form
 */
class ConfigurationForm extends FormBase {

  /**
   * The Menu Entity Index Tracker service.
   *
   * @var \Drupal\menu_entity_index\TrackerInterface
   */
  protected $tracker;

  /**
   * {@inheritdoc}
   */
  public function __construct(TrackerInterface $tracker) {
    $this->tracker = $tracker;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('menu_entity_index.tracker')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'menu_entity_index_configuration_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['menus'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Tracked menus'),
      '#description' => $this->t('Select menus that should be included in menu entity index.'),
      '#options' => $this->tracker->getAvailableMenus(),
      '#default_value' => $this->tracker->getTrackedMenus(),
    ];
    $form['entity_types'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Tracked entity types'),
      '#description' => $this->t('Select entity types that should be included in menu entity index.'),
      '#options' => $this->tracker->getAvailableEntityTypes(),
      '#default_value' => $this->tracker->getTrackedEntityTypes(),
    ];
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = array(
      '#type' => 'submit',
      '#value' => $this->t('Save configuration'),
      '#button_type' => 'primary',
    );
    $form['#theme'] = 'system_config_form';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $menus = array_filter($form_state->getValue('menus'));
    $entity_types = array_filter($form_state->getValue('entity_types'));
    $this->tracker->setConfiguration($menus, $entity_types);
    drupal_set_message($this->t('The configuration options have been saved.'));
  }

}
