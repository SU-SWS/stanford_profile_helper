<?php

declare(strict_types=1);

namespace Drupal\stanford_person_importer\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\stanford_person_importer\Cap;

/**
 * Form alter hooks for stanford_person_importer.
 */
class FormHooks {

  /**
   * Implements hook_field_widget_complete_form_alter().
   */
  #[Hook('field_widget_complete_form_alter')]
  public function fieldWidgetCompleteFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context): void {
    $field_name = $context['items']->getFieldDefinition()->getName();
    if ($field_name == 'su_person_cap_password') {
      // Validate the credentials on the config pages entity form.
      $field_widget_complete_form['widget'][0]['#element_validate'][] = [
        Cap::class,
        'validateCredentials',
      ];
    }
  }

}
