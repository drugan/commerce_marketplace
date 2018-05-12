<?php

namespace Drupal\commerce_marketplace\Form;

use Drupal\commerce_cart\Form\AddToCartForm;

/**
 * Overrides commerce add to cart form.
 */
class MarketplaceAddToCartForm extends AddToCartForm {

  /**
   * The IDs of all forms.
   *
   * @var array
   */
  protected static $formIds = [];

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    if (empty($this->formId)) {
      $this->formId = $this->getBaseFormId();
    }
    $id = $this->formId;

    if (!in_array($id, static::$formIds)) {
      /** @var \Drupal\commerce_order\Entity\OrderItemInterface $order_item */
      $order_item = $this->entity;
      if ($purchased_entity = $order_item->getPurchasedEntity()) {
        $this->formId .= '_' . sha1(serialize($order_item->getPurchasedEntity()->toArray()));
      }
      else {
        $this->formId .= '_' . sha1(serialize($order_item->toArray()));
      }
    }
    else {
      $base_id = $this->getBaseFormId();
      // For the case when on a page 2+ exactly the same purchased entities.
      while (in_array($id, static::$formIds)) {
        $id = $base_id . '_' . sha1($id . $id);
      }
      $this->formId = $id;
    }
    static::$formIds[] = $this->formId;

    return $this->formId;
  }

}
