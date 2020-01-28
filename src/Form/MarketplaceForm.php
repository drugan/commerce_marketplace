<?php

namespace Drupal\commerce_marketplace\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_store\Form\StoreForm;

/**
 * Overrides the store add/edit form.
 */
class MarketplaceForm extends StoreForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    $user = $this->currentUser();
    $admin = $this->entity->getEntityType()->getAdminPermission();
    /** @var \Drupal\commerce_marketplace\StoreStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage('commerce_store');
    $default_store = $storage->loadDefault();
    // If there is no default store saved then the currently edited store will
    // be forced to default. After saving the default that can be reassigned to
    // any other store available.
    $isDefault = TRUE;

    if ($default_store && !$default_store->isNew()) {
      if (!$isDefault = $default_store->uuid() == $this->entity->uuid()) {
        if ($user->hasPermission($admin) || $default_store->getOwnerId() == $user->id()) {
          $link = $default_store->toLink($default_store->getName(), 'edit-form')->toString()->getGeneratedLink();
          $form['warning'] = [
            '#markup' => $this->t('Current default store:') . " <strong>{$link}</strong>",
            '#weight' => $form['name']['#weight'] - 1,
          ];
        }
        else {
          $isDefault = TRUE;
        }
      }
    }

    $form['default']['#description'] = $this->t('The basic role of default store is to be the last in a chain of stores resolved for a particular commerce action. For example, you may have the same product added to different stores and sold in some countries with different taxes applied. So, if no one country condition is met then this product will be handled as if it belongs to the current default store.');

    if ($user->hasPermission($admin)) {
      $form['default']['#title'] = $this->t('Global default store');
      $form['default']['#description'] .= ' ' . $this->t("As admin you may assign for this purpose your own store or any other owner's store. Note that disregarding of this setting each regular store owner have their own default store.");
      // Store limit cannot be set for an admin owner.
      if (!$this->entity->getOwner()->hasPermission($admin)) {
        $uid = $this->entity->getOwnerId();
        $entity_type = $this->entity->bundle();
        $limit = $storage->getStoreLimit($entity_type, $uid);

        $form['marketplace_limit'] = [
          '#type' => 'number',
          '#step' => 1,
          '#min' => 0,
          '#title' => t('The maximum stores'),
          '#description' => t('Override the number of stores of this type allowed to create by the current store owner. Leave 0 to inherit the store type limit (@limit).', ['@limit' => $limit[$entity_type] ?: $this->t('Unlimited')]),
          '#default_value' => $limit[$uid] ?: 0,
        ];
        if (isset($form['uid'])) {
          $form['marketplace_limit']['#weight'] = $form['uid']['#weight'] + 1;
        }
      }
    }
    else {
      $form['default']['#title'] = $this->t('Default store');
      $form['default']['#description'] .= ' ' . $this->t('Only one store might be set as default per a store owner. You can change the default store by assigning it to any other of your stores.');
    }

    $form['default']['#weight'] = $form['name']['#weight'] - 1;
    $form['default']['#default_value'] = $isDefault;
    $form['default']['#disabled'] = $isDefault;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $storage = $this->entityTypeManager->getStorage('commerce_store');
    if (isset($form['marketplace_limit'])) {
      if ($uid_field = isset($form['uid']['widget']['0']['target_id']['#default_value'])) {
        $uid = $form['uid']['widget']['0']['target_id']['#default_value']->id();
      }
      else {
        $uid = $this->entity->getOwnerId();
      }
      $marketplace_limit = $form_state->getValue('marketplace_limit');
      $store_type = $this->entity->bundle();
      /** @var \Drupal\commerce_marketplace\StoreStorageInterface $storage */

      // Owner is changed, so clear limits if they have only one store type.
      if ($uid_field && $uid != $form_state->getValue('uid')[0]['target_id']) {
        $stores = count($storage->getQuery()
          ->condition('uid', $uid)
          ->condition('type', $store_type)->execute());
        $store_type = [
          'delete' => TRUE,
          'store_type' => $store_type,
        ];
      }
      elseif ($marketplace_limit != $form['marketplace_limit']['#default_value']) {
        $limit = $marketplace_limit;
      }
    }

    if ($this->entity->isNew()) {
      $this->entity->enforceIsNew(FALSE);
    }
    parent::save($form, $form_state);

    if (isset($stores) && $stores == 1) {
      // See if there is other stores left, otherwise clear user altogether.
      $stores = count($storage->getQuery()->condition('uid', $uid)->execute());
      if (!$stores) {
        unset($store_type['store_type']);
      }
      $storage->clearStoreLimit($store_type, $uid);
    }
    elseif (isset($limit)) {
      $storage->setStoreLimit($store_type, $limit ?: 0, $this->entity->getOwnerId());
    }
    if ($form_state->getValue('default')) {
      $storage->markAsDefault($this->entity);
    }

    // Redirect to the store/ID page.
    $form_state->setRedirectUrl($this->entity->toUrl());
  }

}
