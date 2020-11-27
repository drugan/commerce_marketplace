<?php

namespace Drupal\commerce_marketplace\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\commerce_shipping\Form\ShippingMethodForm;
use Drupal\commerce_order\Entity\Order;

class MarketplaceShippingMethodForm extends ShippingMethodForm {

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $this->entity->save();
    $this->messenger()->addMessage($this->t('Saved the %label shipping method.', ['%label' => $this->entity->label()]));
    $stores = (array) $form_state->getValue(['stores', 'target_id']);
    $admin = $this->getEntity()->getEntityType()->getAdminPermission();

    if (!$this->currentUser()->hasPermission($admin)) {
      if (count($stores) == 1) {
        $form_state->setRedirect('view.commerce_marketplace_administer_shipping_methods.methods_page', [
          'commerce_store' => reset($stores),
        ]);
      }
      else {
        $form_state->setRedirect('view.commerce_marketplace_administer_stores.user_stores_page', [
          'user' => $this->currentUser()->id(),
        ]);
      }
    }
    else {
     $form_state->setRedirect('entity.commerce_shipping_method.collection');
    }
  }

}
