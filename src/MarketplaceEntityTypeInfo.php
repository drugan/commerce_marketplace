<?php

namespace Drupal\commerce_marketplace;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;

/**
 * Manipulates entity type information.
 *
 * This class contains primarily bridged hooks for compile-time or
 * cache-clear-time hooks. Runtime hooks should be placed in EntityOperations.
 */
class MarketplaceEntityTypeInfo implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * EntityTypeInfo constructor.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   Current user.
   */
  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Adds marketplace operations on entity that supports it.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity on which to define an operation.
   *
   * @return array
   *   An array of operation definitions.
   *
   * @see hook_entity_operation()
   */
  public function entityOperation(EntityInterface $entity) {
    $operations = [];
    if ($entity instanceof StoreInterface && $this->currentUser->hasPermission("view own unpublished commerce_product")) {
      $url = $entity->toUrl();
      $route = 'view.commerce_marketplace_administer_stores.products_page';
      $route_parameters = $url->getRouteParameters();
      $options = $url->getOptions();
      $operations['marketplace_products'] = [
        'title' => $this->t('Products'),
        'weight' => -100,
        'url' => $url->fromRoute($route, $route_parameters, $options),
      ];
      $route = 'view.commerce_marketplace_store_orders.orders_page';
      $route_parameters = $url->getRouteParameters();
      $options = $url->getOptions();
      $operations['marketplace_orders'] = [
        'title' => $this->t('Orders'),
        'weight' => -99,
        'url' => $url->fromRoute($route, $route_parameters, $options),
      ];
      $route = 'view.commerce_marketplace_administer_shipping_methods.methods_page';
      $route_parameters = $url->getRouteParameters();
      $options = $url->getOptions();
      $operations['marketplace_shipping_methods'] = [
        'title' => $this->t('Shipping Methods'),
        'weight' => -98,
        'url' => $url->fromRoute($route, $route_parameters, $options),
      ];

      return $operations;
    }
  }

}
