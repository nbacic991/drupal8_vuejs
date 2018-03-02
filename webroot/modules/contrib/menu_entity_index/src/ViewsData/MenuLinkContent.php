<?php

namespace Drupal\menu_entity_index\ViewsData;

use Drupal\views\EntityViewsData;

/**
 * Provides views integration for menu_link_content entities.
 */
class MenuLinkContent extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    foreach ($data as $table => $table_data) {
      if (in_array('menu_name', array_keys($table_data))) {
        $data[$table]['menu_name']['field']['id'] = 'menu';
        $data[$table]['menu_name']['filter']['id'] = 'menu';
      }
    }

    return $data;
  }

}
