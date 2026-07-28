<?php

declare(strict_types=1);

namespace Drupal\stanford_courses_importer\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Form hooks for stanford_courses_importer.
 */
class FormHooks {

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Alter the config form to add the migrate_tools UI.
   */
  #[Hook('form_config_pages_stanford_courses_importer_form_alter')]
  public function formConfigPagesStanfordCoursesImporterFormAlter(array &$form, FormStateInterface $form_state): array {
    $form['actions']['#type'] = 'fieldset';

    $form['actions']['import'] = [
      '#type' => 'submit',
      '#value' => t('Save & Import'),
      '#name' => 'op',
      '#button_type' => 'primary',
      '#submit' => [
        '::submitForm',
        '::save',
        [self::class, 'importSubmit'],
      ],
    ];

    return $form;
  }

  /**
   * Submit handler for the config form override.
   *
   * @param array $form
   *   The form array.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The form state.
   */
  public static function importSubmit(array &$form, FormStateInterface $form_state): void {
    \Drupal::service('plugin.manager.migration')->clearCachedDefinitions();
    Cache::invalidateTags([
      'config:migrate_plus.migration.stanford_courses_importer',
      'migration_plugins',
    ]);
    $migration_service = \Drupal::service('stanford_migrate')
      ->setBatchExecution(TRUE);
    $migration_service->executeMigrationId('stanford_courses');
  }

}
