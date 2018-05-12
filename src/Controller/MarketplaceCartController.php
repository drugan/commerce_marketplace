<?php

namespace Drupal\commerce_marketplace\Controller;

use Drupal\commerce_cart\Controller\CartController;

/**
 * Overrides the cart page controller.
 */
class MarketplaceCartController extends CartController {

  /**
   * {@inheritdoc}
   */
  public function cartPage() {
    $build = parent::cartPage();
    $carts = $this->cartProvider->getCarts();
    $carts = array_filter($carts, function ($cart) {
      /** @var \Drupal\commerce_order\Entity\OrderInterface $cart */
      return $cart->hasItems();
    });

    if (!isset($build['empty'])) {
      foreach ($build as $key => $value) {
        if (isset($value['#prefix'])) {
          $store_name = $carts[$key]->getStore()->getName();
          $build[$key]['#prefix'] = "<h2 class='cart cart-store-name'>{$store_name}</h2>" . $value['#prefix'];
        }
      }
    }

    return $build;
  }

}
