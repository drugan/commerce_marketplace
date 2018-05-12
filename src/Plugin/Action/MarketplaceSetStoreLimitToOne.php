<?php

namespace Drupal\commerce_marketplace\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Sets store type limit to one.
 *
 * @Action(
 *   id = "commerce_marketplace_set_store_limit_to_one",
 *   label = @Translation("Set store type limit to one"),
 *   type = "commerce_store"
 * )
 */
class MarketplaceSetStoreLimitToOne extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $stores) {
    /** @var \Drupal\commerce_marketplace\StoreStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
    $limits = [];

    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    foreach ($stores as $store) {
      $store_type = $store->bundle();

      if (!isset($limits[$store_type])) {
        $limits[$store_type] = 1;
        $storage->setStoreLimit($store_type, $limits[$store_type]);
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
    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    $admin = $account->hasPermission($store->getEntityType()->getAdminPermission());
    $result = $store->access('update', $account, TRUE)
      ->andIf($store->access('edit', $account, TRUE))
      ->allowedIf($admin);

    return $return_as_object ? $result : $result->isAllowed();
  }

}
