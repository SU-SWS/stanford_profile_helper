<?php

declare(strict_types=1);

namespace Drupal\stanford_events_importer\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\stanford_events_importer\StanfordEventsImporter;

/**
 * Form hooks for stanford_events_importer.
 */
class FormHooks {

  /**
   * Form hooks constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user account.
   */
  public function __construct(protected AccountProxyInterface $currentUser) {}

  /**
   * Implements hook_form_FORM_ID_alter().
   *
   * Alter the config form to add the migrate_tools UI.
   */
  #[Hook('form_config_pages_stanford_events_importer_form_alter')]
  public function formConfigPagesStanfordEventsImporterFormAlter(array &$form, FormStateInterface $form_state): array {
    $form['actions']['#type'] = 'fieldset';
    $form['actions']['#weight'] = 99;
    $form['actions']['import'] = [
      '#type' => 'submit',
      '#value' => t('Save & Import'),
      '#name' => 'op',
      '#button_type' => "primary",
      '#submit' => [
        "::submitForm",
        "::save",
        [self::class, 'importSubmit'],
      ],
    ];

    $form['actions']['update_opts'] = [
      '#type' => 'submit',
      '#value' => t('Update Org & Category Options'),
      '#name' => 'op',
      '#submit' => [[self::class, 'updateOpts']],
      '#access' => $this->currentUser->hasPermission("administer migrations"),
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
      'config:migrate_plus.migration.stanford_localist_importer',
      'migration_plugins',
    ]);
    $migration_service = \Drupal::service('stanford_migrate')
      ->setBatchExecution(TRUE);
    $migration_service->executeMigrationId('stanford_localist_importer');
  }


  /**
   * Fetch and save to state the org & category data.
   */
  public static function updateOpts() {
    $client = \Drupal::httpClient();
    $importer = new StanfordEventsImporter($client);
    $cat_xml_raw = $importer->fetchXML();

    $args = [
      'guids' => '/CategoryList/Category/guid',
      'label' => '/CategoryList/Category/name',
    ];

    // Get the formatted key->value pairs.
    $key_val = $importer->parseXML($cat_xml_raw, $args);

    // Set the state storage for this site.
    \Drupal::cache()
      ->set(StanfordEventsImporter::CACHE_KEY_CAT, $key_val, CacheBackendInterface::CACHE_PERMANENT, ['stanford_events_importer']);

    // Organizations.
    // --------------------------------------------------------------------------.
    $org_xml_raw = $importer->fetchXML('organization-list');

    $args = [
      'guids' => '/OrganizationList/Organization/guid',
      'label' => '/OrganizationList/Organization/name',
    ];

    // Get the formatted key->value pairs.
    $key_val = $importer->parseXML($org_xml_raw, $args);

    // Set the state storage for this site.
    \Drupal::cache()
      ->set(StanfordEventsImporter::CACHE_KEY_ORG, $key_val, CacheBackendInterface::CACHE_PERMANENT, ['stanford_events_importer']);

    // Done.
    \Drupal::messenger()
      ->addStatus('Updated category and organization information.');
  }


}
