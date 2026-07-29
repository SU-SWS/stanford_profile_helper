<?php

declare(strict_types=1);

namespace Drupal\stanford_events\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;

/**
 * Form/field widget hooks for stanford_events.
 */
class FormHooks {

  /**
   * Implements hook_preprocess_field_multiple_value_form().
   *
   * We look for a value that was placed there earlier by
   * stanford_events_field_widget_form_alter() and change the add_more button
   * to use that.
   */
  #[Hook('preprocess_field_multiple_value_form')]
  public function preprocessFieldMultipleValueForm(&$variables): void {
    if ($variables["element"]["#field_name"] == 'su_event_schedule') {
      unset($variables['table']['#header']);
      unset($variables['table']['#tabledrag']);
      foreach ($variables['table']['#rows'] as &$row) {
        foreach (array_keys($row['data']) as $key) {
          if (!empty($row['data'][$key]['class'])
            && array_intersect($row['data'][$key]['class'], [
              'field-multiple-drag',
              'delta-order',
            ])) {
            unset($row['data'][$key]);
          }
        }
      }
    }

    foreach (Element::children($variables['element']) as $child) {
      $child_element = &$variables['element'][$child];

      if (isset($child_element['add_more_button_stanford_person_cta'])) {
        $child_element['add_more_button_stanford_person_cta']['#value'] = t('Add another speaker');
      }

      if ($variables['element']['#field_name'] == "su_schedule_speaker") {
        $variables['element']['add_more']['#value'] = t('Add another speaker');
        $variables['element']['add_more']['add_more_button_stanford_person_cta']['#value'] = t('Add another speaker');
        $variables['element']['add_more_button_stanford_person_cta']['#value'] = t('Add another speaker');
        $variables['button']['add_more']['#value'] = t('Add another speaker');
        $variables['button']['add_more_button_stanford_person_cta']['add_more']['#value'] = t('Add another speaker');
        $variables['button']['add_more_button_stanford_person_cta']['#value'] = t('Add another speaker');
      }
    }
  }

}
