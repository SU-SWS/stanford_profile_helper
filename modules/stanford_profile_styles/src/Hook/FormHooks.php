<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_styles\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Form alter hooks.
 */
class FormHooks {

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_complete_paragraphs_form_alter')]
  public function fieldWidgetCompleteParagraphsFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context): void {
    $field_widget_complete_form['#attached']['library'][] = 'stanford_profile_styles/admin.field_widgets';
  }

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_complete_smartdate_timezone_form_alter')]
  public function fieldWidgetCompleteSmartdateTimezoneFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context): void {
    $field_widget_complete_form['#attached']['library'][] = 'stanford_profile_styles/admin.field_widgets';
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Move your field or group of fields to the node form options vertical tabs.
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    // This has to go through process so that the vertical tabs are rendered.
    $form['#process'][] = [self::class, 'nodeFormWide'];
  }

  /**
   * Moves the advanced group down to below the content as vertical tabs.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state array.
   *
   * @return array
   *   The altered form.
   */
  public static function nodeFormWide(array $form, FormStateInterface &$form_state): array {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $form_state->getFormObject()->getEntity();
    if ($node->bundle() !== "stanford_page") {
      return $form;
    }

    $form['advanced']['#type'] = 'vertical_tabs';
    $form['meta']['#type'] = 'details';
    $form['meta']['#title'] = t('Publishing Information');
    $form['layout_selection']['#type'] = 'details';
    $form['layout_selection']['#title'] = t('Layout Options');
    $form['layout_selection']['#group'] = "advanced";
    $form['layout_selection']['#weight'] = -11;
    $form['#attached']['library'][] = 'stanford_profile_styles/admin.node_forms';

    return $form;
  }

}
