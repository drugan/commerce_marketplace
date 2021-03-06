<?php

namespace Drupal\commerce_marketplace;

use Drupal\profile\ProfileTypeListBuilder;
use Drupal\commerce_marketplace\Controller\MarketplaceEntityListController;

/**
 * Extends ProfileTypeListBuilder class.
 *
 */
class MarketplaceProfileTypeListBuilder extends ProfileTypeListBuilder {

  /**
   * {@inheritdoc}
   */
  public function render($filter = NULL) {
    $build = parent::render();
    $unset = [];
    $this->entitiesKey = property_exists($this, 'entitiesKey') ? $this->entitiesKey : 'table';
    if ($filter instanceof MarketplaceEntityListController) {
      $key = $this->entityType->getKey('id');
      $ids = $this->getEntityIds();
      $unset = $filter->getFilteredEntityIds($key, $this->limit, $ids);
    }
    if ($unset && !empty($build[$this->entitiesKey]['#rows'])) {
      foreach ($build[$this->entitiesKey]['#rows'] as $key => $value) {
        if (isset($unset[$key])) {
          unset($build[$this->entitiesKey]['#rows'][$key]);
        }
      }
    }

    return $build;
  }

}
