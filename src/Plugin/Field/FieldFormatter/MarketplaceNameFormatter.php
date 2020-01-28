<?php

namespace Drupal\commerce_marketplace\Plugin\Field\FieldFormatter;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\commerce_store\Entity\StoreInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\StringFormatter;

/**
 * Plugin implementation of the 'commerce_marketplace_name' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_marketplace_name",
 *   label = @Translation("Store name"),
 *   field_types = {
 *     "string",
 *     "uri",
 *   }
 * )
 */
class MarketplaceNameFormatter extends StringFormatter {

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

    if ($items->getEntity() instanceof StoreInterface) {
      $config = \Drupal::configFactory()->get('commerce_store.settings');
      $global = $this->t('Global default store');
      $owner = $this->t('Owner default store');
      $config = \Drupal::configFactory()->get('commerce_marketplace.settings');
      foreach ($items as $delta => $item) {
        $uuid = $item->getEntity()->uuid();
        $default_store = $item->getEntity()->isDefault() ? $uuid : FALSE;
        $settings = [];
        if ($uuid == $default_store) {
          $settings['global'] = $global;
        }
        $uid = $item->getEntity()->getOwnerId();
        if ($config->get("owners.{$uid}.default_store") == $uuid) {
          $settings['owner'] = $owner;
        }
        $content['type'] = $elements[$delta]['#type'];
        if ($content['type'] == 'inline_template') {
          $content['title'] = $elements[$delta]['#context']['value'];
        }
        elseif ($content['type'] == 'link') {
          $content['title'] = $elements[$delta]['#title']['#context']['value'];
          $content['url'] = $elements[$delta]['#url'];
        }

        $labels[$delta] = [
          '#theme' => 'commerce_marketplace_name',
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
