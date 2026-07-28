<?php

declare(strict_types=1);

namespace Drupal\stanford_intranet\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\stanford_intranet\Plugin\Field\FieldType\EntityAccessFieldType;

/**
 * Form alter hooks for the intranet module.
 */
class FormHooks {

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    if (isset($form[EntityAccessFieldType::FIELD_NAME])) {
      $form[EntityAccessFieldType::FIELD_NAME]['#group'] = 'revision_information';
    }
  }

}
