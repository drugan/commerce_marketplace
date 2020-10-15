<?php

namespace Drupal\commerce_marketplace\Form;

use Drupal\Core\Link;
use Drupal\Core\Form\FormStateInterface;
use Drupal\commerce_product\Form\ProductForm;

/**
 * Overrides the product add/edit form.
 */
class MarketplaceProductForm extends ProductForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $storage = $this->entityTypeManager->getStorage('commerce_store');

    // Stores owned by the current user are counted or all stores available if the
    // user is admin.
    if (!count($storage->loadMultiple())) {
      $can_add = $this->currentUser()->hasPermission($storage->getEntityType()->getAdminPermission());
      if (!$can_add && $store_types = $this->entityManager->getStorage('commerce_store_type')) {
        foreach ($store_types->loadMultiple() as $store_type => $definition) {
          if ($can_add = $this->currentUser()->hasPermission("create {$store_type} commerce_store")) {
            break;
          }
        }
      }
      elseif (empty($store_types)) {
        $link = Link::createFromRoute($this->t('Add a new store type.'), 'entity.commerce_store_type.add_form');
        $markup = $this->t("Products can't be created until a store type and then a store has been added. @link", ['@link' => $link->toString()]);
      }

      if ($can_add) {
        if (!isset($markup)) {
          $link = Link::createFromRoute($this->t('Add a new store.'), 'entity.commerce_store.add_page');
          $markup = $this->t("Products can't be created until a store has been added. @link", ['@link' => $link->toString()]);
        }
      }
      else {
        $markup = $this->t("Oops! Seems the site administrator has granted for you a permission to create products but somehow forgotten about the permission to add your own store. Unfortunately, products can't be created until a store has been added.");
      }
      $form['warning'] = [
        '#markup' => $markup,
      ];

      return $form;
    }

    return parent::buildForm($form, $form_state);
  }

}
