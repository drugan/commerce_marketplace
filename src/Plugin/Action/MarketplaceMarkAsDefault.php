<?php

namespace Drupal\commerce_marketplace\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Marks store as default.
 *
 * @Action(
 *   id = "commerce_marketplace_mark_as_default",
 *   label = @Translation("Mark as default store"),
 *   type = "commerce_store"
 * )
 */
class MarketplaceMarkAsDefault extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $stores) {
    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    /** @var \Drupal\commerce_marketplace\StoreStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
    $single = count($stores) == 1;
    $default_store = $single ? reset($stores) : $storage->loadDefault();

    foreach ($stores as $store) {
      if ($single || $store->uuid() != $default_store->uuid()) {
        $storage->markAsDefault($store);
        // Only one store might be set as default, so for perfomance reasons
        // ignore an attempt to mark the default store in a chain.
        break;
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function execute($store = NULL) {
    // Do nothing.
  }

  /**
   * {@inheritdoc}
   */
  public function access($store, AccountInterface $account = NULL, $return_as_object = FALSE) {
    /** @var \Drupal\commerce_store\Entity\StoreInterface $object */
    $result = $store->access('update', $account, TRUE);

    return $return_as_object ? $result : $result->isAllowed();
  }

}
