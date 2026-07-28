<?php

declare(strict_types=1);

namespace Drupal\jumpstart_ui\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks related to the stat button component.
 */
class StatButtonHooks {

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_field__su_stat_button')]
  public function preprocessFieldSuStatButton(&$variables): void {
    $entity = $variables['element']['#object'];
    if ($entity->get('su_stat_link_style')?->getString() == 'action') {
      $variables['items'][0]['content']['#options']['attributes']['class'] = ['su-link--action'];
    }
  }

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_complete_color_field_widget_box_form_alter')]
  public function fieldWidgetCompleteColorFieldWidgetBoxFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context): void {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = $context['items'];
    if (in_array($items->getFieldDefinition()->getName(), [
      'su_stat_icon_color',
      'su_stat_stat_color',
    ])) {
      $field_widget_complete_form['#states'] = [
        'invisible' => [
          'input[name="su_stat_bg_color[0][color]"]' => ['filled' => TRUE],
        ],
      ];
    }
  }

}
