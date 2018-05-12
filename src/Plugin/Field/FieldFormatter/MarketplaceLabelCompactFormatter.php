<?php

namespace Drupal\commerce_marketplace\Plugin\Field\FieldFormatter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\Plugin\Field\FieldFormatter\EntityReferenceLabelFormatter;

/**
 * Plugin implementation of the 'commerce_marketplace_label_compact' formatter.
 *
 * @FieldFormatter(
 *   id = "commerce_marketplace_label_compact",
 *   label = @Translation("Label Compact"),
 *   field_types = {
 *     "entity_reference"
 *   }
 * )
 */
class MarketplaceLabelCompactFormatter extends EntityReferenceLabelFormatter {

  /**
   * {@inheritdoc}
   */
  public static function defaultSettings() {
    return [
      'summary' => t('Total'),
      'empty' => t('No options'),
      'open' => FALSE,
      'list' => 'ul',
      'separator' => ', ',
      'max' => 0,
      'offset' => 0,
      'attributes' => [],
      'content_attributes' => [],

    ] + parent::defaultSettings();
  }

  /**
   * {@inheritdoc}
   */
  public function settingsForm(array $form, FormStateInterface $form_state) {
    $form = parent::settingsForm($form, $form_state);
    extract($this->getSettings());

    $form['summary'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Base title for the compact list. Leave empty to display only labels' counter in the title."),
      '#default_value' => $summary,
    ];
    $form['empty'] = [
      '#type' => 'textfield',
      '#title' => $this->t("The placeholder for an empty list having no options."),
      '#default_value' => $empty,
    ];
    $form['open'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Show the list's widget expanded by default."),
      '#default_value' => $open,
    ];
    $form['list'] = [
      '#type' => 'radios',
      '#title' => $this->t('List style'),
      '#options' => [
        'ul' => $this->t('Unordered list'),
        'ol' => $this->t('Ordered list'),
        'simple' => $this->t('Separated by a string inline list'),
      ],
      '#default_value' => $list,
    ];
    $form['separator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Separator'),
      '#default_value' => $separator,
      '#states' => [
        'visible' => [
          ':input[name$="[list]"]' => ['value' => 'simple'],
        ],
      ],
    ];
    $form['max'] = [
      '#type' => 'number',
      '#title' => $this->t('The maximum number of labels to display. Leave 0 to display all labels.'),
      '#min' => 0,
      '#step' => 1,
      '#default_value' => $max,
    ];
    $form['offset'] = [
      '#type' => 'number',
      '#title' => $this->t('Starting from a label (the first label is 0).'),
      '#min' => 0,
      '#step' => 1,
      '#default_value' => $offset,
    ];
    if ($form_state->getFormObject()->getFormId() == 'views_ui_config_item_form') {
      $form['warning'] = [
        '#markup' => t("Note that <strong>MULTIPLE FIELD SETTINGS</strong> below should be set as the following:<br>
         checked <strong>Display all values in the same row</strong>,<br>
         checked <strong>Simple separator</strong>,<br>
         <strong>Separator</strong> have no effect, might be empty,<br>
         <strong>Display</strong> 0, <strong>starting from</strong> 0,<br>
         the <strong>Reversed</strong> and <strong>First and last only</strong> might be in any status that you need."),
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function settingsSummary() {
    $settings_summary = [];
    $none = $this->t('None');
    $settings = $this->getSettings();
    unset($settings['attributes'], $settings['content_attributes']);
    extract($settings);
    $settings['link'] = $link == 1 ? t('Enabled') : $link;
    $settings['open'] = $open == 1 ? t('Yes') : $open;
    $settings['list'] = $list == 'ol' ? t('Ordered') : ($list == 'ul' ? t('Unordered') : t('Simple'));

    foreach ($settings as $name => $value) {
      $value = empty($settings[$name]) ? $none : $value;
      $settings_summary[] = "{$name}: {$value}";
    }

    return $settings_summary;
  }

  /**
   * {@inheritdoc}
   */
  public function viewElements(FieldItemListInterface $items, $langcode) {
    $elements = parent::viewElements($items, $langcode);
    $settings = $this->getSettings();
    extract($settings);
    $labels = [];
    $i = 1;

    foreach ($elements as $delta => $element) {
      if ($offset > $delta || ($max && $max < $i)) {
        unset($elements[$delta]);
        continue;
      }
      $i++;
    }

    $settings['total'] = $d = isset($delta) ? $delta + 1 : 0;
    $i = $i - 1;
    $count = $d == $i ? $i : $this->t('@i out of @d', ['@i' => $i, '@d' => $d]);
    // Allow usage of special characters as the widget title, empty list
    // placeholder and simple inline list items separator.
    $settings['summary'] = Html::escape("{$summary} ({$count})");
    $settings['separator'] = Html::escape($separator);
    $settings['empty'] = Html::escape($empty);

    $labels[] = [
      '#theme' => 'commerce_marketplace_details_compact',
      '#settings' => $settings,
      '#content' => $elements,
      '#attached' => [
        'library' => ['commerce_marketplace/marketplace_default'],
      ],
    ];

    return $labels;
  }

}
