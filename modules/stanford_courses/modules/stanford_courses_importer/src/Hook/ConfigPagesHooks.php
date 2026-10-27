<?php

declare(strict_types=1);

namespace Drupal\stanford_courses_importer\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\config_pages\ConfigPagesInterface;

/**
 * Config pages hooks for stanford_courses_importer.
 */
class ConfigPagesHooks {

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('config_pages_presave')]
  public function configPagesPresave(ConfigPagesInterface $entity): void {
    // The config page overrides the migration status, which decides if the
    // migration plugin exists, so the definitions have to be rebuilt.
    if ($entity->bundle() == 'stanford_courses_importer') {
      \Drupal::service('plugin.manager.migration')->clearCachedDefinitions();
    }
  }

}
