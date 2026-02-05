<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Plugin\Field\FieldWidget;

use Drupal\Core\Field\Attribute\FieldWidget;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

/**
 * Defines the 'time_duration' field widget.
 */
#[FieldWidget(
  id: 'time_duration',
  label: new TranslatableMarkup('Time Duration'),
  field_types: ['integer'],
)]
final class TimeDurationWidget extends WidgetBase {

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state): array {
    $item = $items[$delta];
    $value = $item->toArray();
    $time = $value['value'];

    $hours = $time ? floor($time / 60 / 60) : NULL;
    $minutes = $time ? floor(($time - ($hours * 60 * 60)) / 60) : NULL;
    $seconds = $time ? $time % 60 : NULL;

    $element['time'] = $element + [
        '#type' => 'fieldset',
        '#attributes' => ['class' => ['time-duration']],
        '#attached' => ['library' => ['stanford_profile_helper/time_duration_widget']],
      ];
    $element['time']['hour'] = [
      '#type' => 'number',
      '#title' => $this->t('Hours'),
      '#min' => 0,
      '#max' => 99,
      '#default_value' => $hours ? $hours : NULL,
    ];
    $element['time']['min'] = [
      '#type' => 'number',
      '#title' => $this->t('Minutes'),
      '#min' => 0,
      '#max' => 59,
      '#default_value' => ($minutes || $hours) ? $minutes : NULL,
    ];
    $element['time']['sec'] = [
      '#type' => 'number',
      '#title' => $this->t('Seconds'),
      '#min' => 0,
      '#max' => 59,
      '#default_value' => ($hours || $minutes || $seconds) ? $seconds : NULL,
    ];
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    $new_values = [];
    // Remove values that don't have any time set.
    $values = array_filter($values, fn($value) => $value['time']['hour'] || $value['time']['min'] || $value['time']['sec']);
    foreach ($values as $value) {
      $hours = $value['time']['hour'] ?: 0;
      $min = $value['time']['min'] ?: 0;
      $sec = $value['time']['sec'] ?: 0;
      $new_values[] = ['value' => $sec + ($min * 60) + ($hours * 60 * 60)];
    }
    return $new_values;
  }

}
