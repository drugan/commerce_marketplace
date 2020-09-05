<?php

namespace Drupal\commerce_marketplace\Resolver;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\commerce_product\Entity\ProductInterface;
use Drupal\commerce_store\Resolver\StoreResolverInterface;

/**
 * Returns the product's default store, when a product is present in the URL.
 *
 * Ensures that the current store is always correct when viewing or editing the
 * product.
 */
class MarketplaceProductDefaultStoreResolver implements StoreResolverInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Constructs a new MarketplaceProductDefaultStoreResolver object.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The enttiy manager.
   */
  public function __construct(RouteMatchInterface $route_match, EntityTypeManagerInterface $entity_type_manager) {
    $this->routeMatch = $route_match;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function resolve() {
    $product = $this->routeMatch->getParameter('commerce_product');
    if ($product instanceof ProductInterface) {
      $storage = $this->entityTypeManager->getStorage('commerce_store');
      // The default store specific for this particular product owner. To return
      // all the stores belonging to the owner, do this:
      // $stores = $storage->loadMultiple(NULL, $product->getOwner());
      return $storage->loadDefault($product->getOwner());
    }

    return NULL;
  }

}
