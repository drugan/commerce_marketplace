<?php

namespace Drupal\commerce_marketplace\Plugin\Action;

use Drupal\Core\Action\ActionBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Marks store as owner default.
 *
 * @Action(
 *   id = "commerce_marketplace_mark_as_owner_default",
 *   label = @Translation("Mark as owner default store"),
 *   type = "commerce_store"
 * )
 */
class MarketplaceMarkAsOwnerDefault extends ActionBase {

  /**
   * {@inheritdoc}
   */
  public function executeMultiple(array $stores) {
    $config = \Drupal::configFactory()->getEditable("commerce_marketplace.settings");
    $owners = [];

    /** @var \Drupal\commerce_store\Entity\StoreInterface $store */
    foreach ($stores as $store) {
      if (!isset($permission)) {
        $permission = $store->getEntityType()->getAdminPermission();
      }
      $owner = $store->getOwner();
      $owner_is_admin = $owner->hasPermission($permission);
      $uid = $store->getOwnerId();
      $uuid = $store->uuid();

      if ($owner_is_admin) {
        $name = $owner->getDisplayName();
        $msg = $this->t('The %name store cannot be set as owner default because they have admin permission and should use a global default store.', ['%name' => $name]);
        $this->messenger()->addWarning($msg, FALSE);
      }
      elseif (!isset($owners[$uid])) {
        if ($config->get("owners.{$uid}.default_store") != $uuid) {
          $owners[$uid] = $uuid;
          $save = $config->set("owners.{$uid}.default_store", $owners[$uid]);
        }
      }
    }
    if (isset($save)) {
      $config->save();
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
