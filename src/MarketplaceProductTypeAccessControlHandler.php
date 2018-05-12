<?php

namespace Drupal\commerce_marketplace;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce\CommerceBundleAccessControlHandler;

/**
 * Defines the access control handler for the product type entity type.
 *
 * @see \Drupal\commerce_product\Entity\ProductType
 */
class MarketplaceProductTypeAccessControlHandler extends CommerceBundleAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    switch ($operation) {
      case 'view':
        // As there is no 'view own commerce_product_type' or
        // "view {$entity->id()} commerce_product_type" permission then allow
        // user to view product type of the product which can be updated by the
        // user.
        return AccessResult::allowedIfHasPermission($account, "update own {$entity->id()} commerce_product");

      default:
        return parent::checkAccess($entity, $operation, $account);
    }
  }

}
