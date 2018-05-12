<?php

namespace Drupal\commerce_marketplace;

use Drupal\entity\EntityAccessControlHandler;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;

/**
 * Overrides the Store entity access handler.
 */
class MarketplaceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    // Only allow users to create limited number of permitted store types.
    $result = parent::checkCreateAccess($account, $context, $entity_bundle);
    if ($result->isNeutral() || !$result->isForbidden()) {
      if (!$allowed = $account->hasPermission($this->entityType->getAdminPermission())) {
        if ($allowed = $account->hasPermission("create {$entity_bundle} commerce_store")) {
          $storage = \Drupal::entityTypeManager()->getStorage($this->entityTypeId);
          $uid = $account->id();
          $limit = $storage->getStoreLimit($entity_bundle, $uid);
          $limit = $limit[$uid] ?: $limit[$entity_bundle];
          if ($limit) {
            $stores = $storage->getQuery()->execute();
            $allowed = count($stores) < $limit;
          }
        }
      }
      $result = AccessResult::allowedIf($allowed);
    }

    return $result;
  }

  /**
   * {@inheritdoc}
   */
  protected function checkFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, FieldItemListInterface $items = NULL) {
    $result = parent::checkFieldAccess($operation, $field_definition, $account, $items);
    if ($result->isNeutral() || !$result->isForbidden()) {
      if ($operation == 'edit' && $field_definition->getName() == 'uid') {
        $admin = $account->hasPermission($this->entityType->getAdminPermission());
        $result = AccessResult::allowedIf($admin);
      }
    }

    return $result;
  }

}
