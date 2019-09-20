<?php

namespace Drupal\commerce_marketplace\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Defines a generic controller to list entities.
 */
class MarketplaceEntityListController extends ControllerBase {

  /**
   * {@inheritdoc}
   */
  protected $builder;

  /**
   * {@inheritdoc}
   */
  protected $args;

  /**
   * {@inheritdoc}
   */
  public function getFilteredEntityIds($key, $limit, $all) {
    $ids = $unset = [];
    if ($starts_with = !empty($this->args['starts_with'])) {
      $ids = $this->args['starts_with'];
    }
    elseif ($not_starts_with = !empty($this->args['not_starts_with'])) {
      $ids = $this->args['not_starts_with'];
    }
    if ($ids) {
      foreach ($ids as $string) {
        $query = $this->builder->getStorage()->getQuery();
        if ($string && is_string($string)) {
          $query->condition('vid', $string, 'STARTS_WITH');
          $query->sort($key);
          if ($limit) {
            $query->pager($limit);
          }
          $unset += $query->execute();
        }
      }
      if ($starts_with) {
        $unset = array_diff($all, $unset);
      }
      elseif ($not_starts_with) {
        $unset = array_intersect($all, $unset);
      }
    }

    return $unset;
  }
  /**
   * Provides the listing page for any entity type.
   *
   * @param string $entity_type
   *   The entity type to render.
   *
   * @return array
   *   A render array as expected by
   *   \Drupal\Core\Render\RendererInterface::render().
   */
  public function listing($entity_type, $args = []) {
    $this->builder = $this->entityTypeManager()->getListBuilder($entity_type);
    $this->args = $args;
    return $this->builder->render($this);
  }

}
