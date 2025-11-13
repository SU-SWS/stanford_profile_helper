<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Element;

/**
 * Hooks that relate to algolia search indexing.
 */
class ViewFieldHooks {

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_complete_viewfield_select_form_alter')]
  function viewfieldFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context) {
    $field_widget_complete_form['#attached']['library'][] = 'stanford_profile_helper/viewfield_autocomplete';
    $deltas = Element::children($field_widget_complete_form['widget']);
    foreach ($deltas as $delta) {
      $field_widget_complete_form['widget'][$delta]['#attributes']['class'][] = 'viewfield-autocomplete';

      $view = $field_widget_complete_form['widget'][$delta]['target_id']['#default_value'] ?: 'none';
      $display = $field_widget_complete_form['widget'][$delta]['display_id']['#default_value'] ?: 'none';

      $field_widget_complete_form['widget'][$delta]['view_options']['arguments'] += [
        '#autocomplete_route_name' => 'stanford_profile_helper.autocomplete.viewfield',
        '#autocomplete_route_parameters' => [
          'view' => $view,
          'display' => $display,
        ],
      ];
    }
  }

}
