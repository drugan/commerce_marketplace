<?php

namespace Drupal\commerce_marketplace;

use Drupal\taxonomy\VocabularyListBuilder;
use Drupal\commerce_marketplace\Controller\MarketplaceEntityListController;

/**
 * Extends VocabularyListBuilder class.
 *
 * @see \Drupal\taxonomy\Entity\Vocabulary
 */
class MarketplaceVocabularyListBuilder extends VocabularyListBuilder {

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
    if ($unset && !empty($build[$this->entitiesKey])) {
      foreach ($build[$this->entitiesKey] as $key => $value) {
        if (isset($unset[$key])) {
          unset($build[$this->entitiesKey][$key]);
        }
      }
    }

    return $build;
  }

}
