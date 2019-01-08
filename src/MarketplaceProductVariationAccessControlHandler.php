<?php

namespace Drupal\commerce_marketplace;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_product\ProductVariationAccessControlHandler;
use Drupal\commerce_product\Entity\Product;

/**
 * Overrides an access control handler for product variations.
 */
class MarketplaceProductVariationAccessControlHandler extends ProductVariationAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    $result = AccessResult::allowedIfHasPermission($account, $this->entityType->getAdminPermission());
    if ($result->isAllowed()) {
      return $result;
    }
    $result = AccessResult::allowedIfHasPermission($account, "manage $entity_bundle commerce_product_variation");
    $parts = explode('/', \Drupal::request()->getRequestUri());
    if (isset($parts[1]) && $parts[1] == 'product' && !empty($parts[2])) {
      if ($result->isAllowed() && (Product::load($parts[2])->getOwnerId() == $account->id())) {
        return $result;
      }
      else {
        return AccessResult::forbidden();
      }
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($account->hasPermission($this->entityType->getAdminPermission())) {
      return AccessResult::allowed()->cachePerPermissions();
    }
    /** @var \Drupal\commerce_product\Entity\ProductVariationInterface $entity */
    $product = $entity->getProduct();
    if (!$product || (($operation != 'view') && !$product->access($operation, $account))) {
      // The product variation is malformed or current user does not have
      // access to perform an operation on the product so, the corresponding
      // operation on the variaiton is not allowed too.
      return AccessResult::forbidden()->addCacheableDependency($entity);
    }

    if ($operation == 'view') {
      $result = $product->access('view', $account, TRUE);
    }
    else {
      $bundle = $entity->bundle();
      $result = AccessResult::allowedIfHasPermission($account, "manage $bundle commerce_product_variation");
    }

    return $result;
  }

}
