<?php

namespace Drupal\commerce_marketplace\Plugin\views\argument_default;

use Drupal\user\Plugin\views\argument_default\User;
use Drupal\user\UserInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Overrides default argument plugin to extract a user from request.
 */
class MarketplaceUser extends User {

  /**
   * {@inheritdoc}
   */
   public function getArgument() {
    if ($parameters = $this->routeMatch->getParameters()) {
      foreach ($parameters->all() as $entity) {
        if ($entity instanceof UserInterface) {
          return $entity->id();
        }
        elseif ($entity instanceof EntityOwnerInterface) {
          return $entity->getOwnerId();
        }
       }
     }
   }

}
