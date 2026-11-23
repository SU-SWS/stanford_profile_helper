<?php

declare(strict_types=1);

namespace Drupal\stanford_courses_importer\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\config_pages\ConfigPagesInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;

/**
 * Config pages hooks for stanford_courses_importer.
 */
class ConfigPagesHooks {

  /**
   * Constructs the hook class.
   *
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migrationManager
   *   Migration plugin manager service.
   */
  public function __construct(protected MigrationPluginManagerInterface $migrationManager) {}

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('config_pages_presave')]
  public function configPagesPresave(ConfigPagesInterface $entity): void {
    // The config page overrides the migration status, which decides if the
    // migration plugin exists, so the definitions have to be rebuilt.
    if ($entity->bundle() == 'stanford_courses_importer') {
      $this->migrationManager->clearCachedDefinitions();
    }
  }

}
