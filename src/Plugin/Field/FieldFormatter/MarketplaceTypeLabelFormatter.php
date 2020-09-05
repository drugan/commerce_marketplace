<?php

namespace Drupal\commerce_marketplace\Plugin\Field\FieldFormatter;

use Drupal\user\UserInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'commerce_marketplace_type_label' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_marketplace_type_label",
 *   label = @Translation("Store type label"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MarketplaceTypeLabelFormatter extends EntityReferenceLabelFormatter {

  /**
   * The storage.
   *
   * @var \Drupal\commerce_marketplace\StoreStorageInterface
   */
  protected $storage;

  /**
   * The current user has admin permission.
   *
   * @var bool
   */
  protected $currentUserAdmin;


  /**
   * The store types limits.
   *
   * @var array
   */
  protected $limits = [];

  /**
   * The store type stores number created by an owner.
   *
   * @var array
   */
  protected $usedStores = [];

  /**
   * {@inheritdoc}
   */
  public function __construct($plugin_id, $plugin_definition, FieldDefinitionInterface $field_definition, array $settings, $label, $view_mode, array $third_party_settings) {
    parent::__construct($plugin_id, $plugin_definition, $field_definition, $settings, $label, $view_mode, $third_party_settings);
    $this->storage = \Drupal::entityTypeManager()->getStorage('commerce_store');
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $labels = [];
    $store = $items->getEntity();
    if ($store instanceof StoreInterface) {
      $owner = $store->getOwner();
      $store_type = $store->bundle();
      $uid = $store->getOwnerId();
      $admin_permission = $store->getEntityType()->getAdminPermission();
      $owner_is_admin = $owner->hasPermission($admin_permission);
      if ($this->currentUserAdmin === NULL) {
        $current_uid = \Drupal::currentUser()->id();
        $this->currentUserAdmin = $owner->load($current_uid)->hasPermission($admin_permission);
      }
      if (!isset($this->limits[$uid][$store_type])) {
        $limit = $this->storage->getStoreLimit($store_type, $uid);
        $this->limits['stores'][$store_type] = $limit[$store_type];
        $this->limits[$uid][$store_type] = $limit[$uid];
      }
      $store_type_limit = $this->limits['stores'][$store_type];
      $owner_limit = $this->limits[$uid][$store_type];
      $is_limit = $owner_limit ?: $store_type_limit;
      if ($this->currentUserAdmin && !$owner_is_admin) {
        if (!isset($this->usedStores[$uid][$store_type])) {
          $used = count($this->storage->getQuery()->condition('uid', $uid)->condition('type', $store_type)->execute());
          $this->usedStores[$uid][$store_type] = $used;
        }
        $used_stores = ', ' . $this->t('used: @used', ['@used' => $this->usedStores[$uid][$store_type]]);
        $global_limit = $store_type_limit ? $this->t('Global limit:') . " {$store_type_limit}" : $this->t('Unlimited');
        $owner_limit = $this->t('Owner limit: @limit', ['@limit' => $owner_limit ?: $this->t('inherit')]);
        $owner_limit .= $used_stores;
      }
      elseif (!$owner_is_admin && $is_limit) {
        if (!isset($this->usedStores[$uid][$store_type])) {
          $used = count($this->storage->getQuery()->condition('type', $store_type)->execute());
          $this->usedStores[$uid][$store_type] = $used;
        }
        $used_stores = ', ' . $this->t('used: @used', ['@used' => $this->usedStores[$uid][$store_type]]);
        $your_limit = $this->t('Your limit: @limit', ['@limit' => $is_limit]) . $used_stores;
      }

      foreach ($items as $delta => $item) {
        $settings = [];
        if ($this->currentUserAdmin && !$owner_is_admin) {
          $settings['global'] = $global_limit;
          $settings['owner'] = $owner_limit;
        }
        elseif (!$owner_is_admin && $is_limit) {
          $settings['owner'] = $your_limit;
        }
        $content['type'] = isset($elements[$delta]['#type']) ? $elements[$delta]['#type'] : 'plain_text';
        if ($content['type'] == 'link') {
          if ($this->currentUserAdmin) {
            $content['url'] = $elements[$delta]['#url'];
          }
          else {
            $content['type'] = 'plain_text';
          }
          $content['title'] = isset($elements[$delta]['#title']) ? $elements[$delta]['#title'] : 'A type';
        }
        else {
          $content['title'] = isset($elements[$delta]['#plain_text']) ? $elements[$delta]['#plain_text'] : 'A type';
        }
        $labels[$delta] = [
          '#theme' => 'commerce_marketplace_type_label',
          '#settings' => $settings,
          '#content' => $content,
          '#attached' => [
            'library' => ['commerce_marketplace/marketplace_default'],
          ],
        ];
      }
    }

    return $labels;
  }

}
