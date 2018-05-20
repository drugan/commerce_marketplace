<?php

namespace Drupal\commerce_marketplace;

use Drupal\commerce_store\StoreStorage;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_store\Entity\StoreInterface;

/**
 * Overrides the store storage class.
 */
class MarketplaceStorage extends StoreStorage {

  /**
   * {@inheritdoc}
   */
  public function loadDefault(AccountInterface $user = NULL) {
    $default = NULL;
    if ($uid = $this->getCurrentUserId($user)) {
      $config = $this->configFactory->get('commerce_marketplace.settings');
      $uuid = $config->get("owners.{$uid}.default_store");
      $ids = parent::getQuery()->condition('uid', $uid)->execute();
    }
    else {
      $config = $this->configFactory->get('commerce_store.settings');
      $uuid = $config->get('default_store');
      $ids = parent::getQuery()->execute();
    }

    if ($ids) {
      $stores = parent::loadMultiple($ids);
      if ($uuid) {
        foreach ($stores as $store) {
          if ($store->uuid() == $uuid) {
            $default = $store;
            break;
          }
        }
      }
      else {
        $store = end($stores);
      }
    }
    else {
      $stores = parent::loadMultiple();
    }

    if (!$default && isset($store)) {
      // This is the case when previously assigned default store was
      // deleted, so we need to return at least the last found store.
      $default = $store;
      $default->enforceIsNew();
      if (count($stores) > 1) {
        drupal_set_message(t('No one default store is assigned yet. Note that it is recommended to have one explicitly assigned otherwise the last found store will be dimmed as the default. This may lead to unexpected behaviour.'), 'warning', FALSE);
      }
    }
    elseif (!$default && $stores) {
      // As a last resort let's return the first store in the list.
      $default = reset($stores);
    }

    return $default;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL, AccountInterface $user = NULL) {
    $stores = [];
    if (!$ids && $user) {
      $ids = parent::getQuery()->condition('uid', $user->id())->execute();
    }
    elseif (!$ids) {
      $ids = $this->getQuery()->execute();
    }

    if ($ids) {
      $stores = parent::loadMultiple($ids);
    }

    return $stores;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuery($conjunction = 'AND') {
    $query = parent::getQuery($conjunction);

    // If the current user is not an admin ($uid === FALSE) we restrict the
    // query to the stores owned by the user or, if the $uid === 0, return the
    // query for the anonymous user which should not be the owner of any store.
    $uid = $this->getCurrentUserId();
    if ($uid !== FALSE) {
      $query->condition('uid', $uid);
    }

    return $query;
  }

  /**
   * {@inheritdoc}
   */
  public function markAsDefault(StoreInterface $store) {
    $uid = $this->getCurrentUserId();
    // When the current user is admin the global default store is saved.
    if ($uid === FALSE) {
      $config = $this->configFactory->getEditable('commerce_store.settings');
      if ($config->get('default_store') != $store->uuid()) {
        $config->set('default_store', $store->uuid());
        $config->save();
      }
    }
    elseif ($uid) {
      $config = $this->configFactory->getEditable('commerce_marketplace.settings');
      if ($config->get("owners.{$uid}.default_store") != $store->uuid()) {
        $config->set("owners.{$uid}.default_store", $store->uuid());
        $config->save();
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function setStoreLimit($store_type, $limit, $uid = NULL) {
    $config = $this->configFactory->getEditable('commerce_marketplace.settings');
    if ($store_type && $uid && is_numeric($limit)) {
      $config->set("owners.{$uid}.store_types.{$store_type}.limit", $limit);
      $config->save();
    }
    elseif ($store_type && is_numeric($limit)) {
      $config->set("store_types.{$store_type}.limit", $limit);
      $config->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getStoreLimit($store_type = NULL, $uid = NULL) {
    $config = $this->configFactory->get('commerce_marketplace.settings');
    if (!$store_type) {
      return $config->getRawData();
    }
    $limit = $config->get("store_types.{$store_type}.limit");
    if ($uid) {
      $limit = [
        $store_type => $limit,
        $uid => $config->get("owners.{$uid}.store_types.{$store_type}.limit"),
      ];
    }

    return $limit;
  }

  /**
   * {@inheritdoc}
   */
  public function clearStoreLimit($store_type = NULL, $uid = NULL) {
    $limit = '.limit';
    if ($delete = isset($store_type['delete'])) {
      $limit = '';
      // If store_type is empty then configuration on all types will be cleared.
      $store_type = isset($store_type['store_type']) ? $store_type['store_type'] : NULL;
    }
    $config = $this->configFactory->getEditable("commerce_marketplace.settings");

    if ($store_type && $uid) {
      if ($config->get("owners.{$uid}.store_types.{$store_type}{$limit}") !== NULL) {
        $save = $config->clear("owners.{$uid}.store_types.{$store_type}{$limit}");
        if ($config->get("owners.{$uid}.store_types") === []) {
          $config->clear("owners.{$uid}.store_types");
        }
        if ($config->get("owners.{$uid}") === []) {
          $config->clear("owners.{$uid}");
        }
      }
    }
    elseif ($store_type && !$delete) {
      if ($config->get("store_types.{$store_type}{$limit}") !== NULL) {
        $save = $config->clear("store_types.{$store_type}{$limit}");
      }
    }
    elseif ($delete && $uid) {
      // Clear the requested uid from configuration altogether.
      if ($config->get("owners.{$uid}") !== NULL) {
        $save = $config->clear("owners.{$uid}");
      }
    }
    else {
      // Clear all limits.
      $stores = $this->loadMultiple() ?: [];
      $owner_id = $uid;
      $store_bundle = $store_type;
      // First, clear store type that is not bundled with any store.
      if ($config->get("store_types.{$store_type}{$limit}") !== NULL) {
        $save = $config->clear("store_types.{$store_type}{$limit}");
      }
      foreach ($stores as $store) {
        $store_type = $store_bundle ?: $store->bundle();
        $uid = $owner_id ?: $store->getOwnerId();
        if (!$delete && $config->get("owners.{$uid}.store_types.{$store_type}{$limit}") !== NULL) {
          $save = $config->clear("owners.{$uid}.store_types.{$store_type}{$limit}");
        }
        if ($config->get("store_types.{$store_type}{$limit}") !== NULL) {
          $save = $config->clear("store_types.{$store_type}{$limit}");
        }
      }
    }

    if (isset($save)) {
      $config->save();
    }
  }

  /**
   * Helper method to check the current user access to a commerce store.
   *
   * @return false|int
   *   FALSE if the user is admin; user ID if the user has permission to view
   *   own store; an anonymous user ID (0) otherwise.
   */
  protected function getCurrentUserId(AccountInterface $user = NULL) {
    $user = $user ?: \Drupal::currentUser();
    $uid = FALSE;

    if (!$user->hasPermission($this->entityType->getAdminPermission())) {
      $uid = in_array('commerce_marketplace_owner', $user->getRoles()) ? $user->id() : 0;
    }

    return $uid;
  }

}
