<?php

namespace Drupal\commerce_marketplace;

use Drupal\commerce\CommerceBundleAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Overrides the Store type access handler.
 */
class MarketplaceTypeAccessControlHandler extends CommerceBundleAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    $result = parent::checkAccess($entity, $operation, $account);
    if ($result->isNeutral() || !$result->isForbidden()) {
      if ($operation == 'view') {
        if (!$allowed = $account->hasPermission($this->entityType->getAdminPermission())) {
          // Having permission to update own store also implies viewing it.
          $allowed = $account->hasPermission("update own {$entity->id()} commerce_store");
        }
        // Allow store owner view store type label in views.
        $result = AccessResult::allowedIf($allowed);
      }
    }

    return $result;
  }

}
