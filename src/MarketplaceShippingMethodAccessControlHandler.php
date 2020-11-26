<?php

namespace Drupal\commerce_marketplace;

use Drupal\entity\EntityAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Overrides the Shipping Method entity access handler.
 */
class MarketplaceShippingMethodAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $result = parent::checkAccess($entity, $operation, $account);
    if ($result->isAllowed()) {
      return $result;
    }

    $stores = $entity->getStores();
    foreach ($stores as $store) {
      if (!$store->access('update', $account)) {
        return $result;
      }
    }
    if ($stores && ($operation == 'update' || $operation == 'delete')) {
      // Shipping method belongs to store(s) so we sync permissions here.
      // Only allowed stores' list will be visible on a method edit page.
      $result = $result::allowed();
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);
    if ($result->isAllowed()) {
      return $result;
    } 
   
    $stores = \Drupal::entityTypeManager()->getStorage('commerce_store')->loadMultiple();
    foreach ($stores as $store) {
      if ($store->access('update', $account)) {
        // At least to one of the stores a shipping method can be added.
        return AccessResult::allowed();
      }
    }

    return $result;
  }

}
