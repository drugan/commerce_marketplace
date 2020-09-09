<?php

namespace Drupal\commerce_marketplace\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Increases store type limit by one for an owner.
 *
 * @Action(
 *   id = "commerce_marketplace_increase_owner_limit_by_one",
 *   label = @Translation("Increase owner store limit by one"),
 *   type = "commerce_store"
 * )
 */
class MarketplaceIncreaseOwnerLimitByOne extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $stores) {
    /** @var \Drupal\commerce_marketplace\StoreStorageInterface $storage */
    $storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
    $limits = [];

    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    foreach ($stores as $store) {
      if (!isset($permission)) {
        $permission = $store->getEntityType()->getAdminPermission();
      }
      $owner = $store->getOwner();
      $owner_is_admin = $owner->hasPermission($permission);
      $store_type = $store->bundle();
      $uid = $store->getOwnerId();

      if ($owner_is_admin) {
        $name = $owner->getDisplayName();
        $msg = $this->t('The store type limit cannot be set for the %name because they have admin permission and should not have any limits.', ['%name' => $name]);
        $this->messenger()->addWarning($msg, FALSE);
      }
      elseif (!isset($limits[$uid][$store_type])) {
        $limit = $storage->getStoreLimit($store_type, $uid);
        $limits[$uid][$store_type] = $limit[$uid] + 1;
        $storage->setStoreLimit($store_type, $limits[$uid][$store_type], $uid);
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
