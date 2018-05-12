<?php

namespace Drupal\commerce_marketplace;

use Drupal\entity\EntityAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Overrides the Currency entity access handler.
 */
class MarketplaceCurrencyAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $result = parent::checkAccess($entity, $operation, $account);
    if ($result->isNeutral() || !$result->isForbidden()) {
      if ($operation == 'view') {
        if (!$allowed = $account->hasPermission($this->entityType->getAdminPermission())) {
          // Currency code or symbol does not require specific permission as it
          // can be viewed by user in a product price component.
          $allowed = $account->hasPermission("view commerce_product");
        }
        // Allow store owner view currency code in views.
        $result = AccessResult::allowedIf($allowed);
      }
    }

    return $result;
  }

}
