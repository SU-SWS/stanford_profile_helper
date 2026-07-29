<?php

declare(strict_types=1);

namespace Drupal\stanford_events_importer\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\config_pages\ConfigPagesInterface;

/**
 * Config pages hooks for stanford_events_importer.
 */
class ConfigPagesHooks {

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('config_pages_presave')]
  public function configPagesPresave(ConfigPagesInterface $entity): void {
    // Clear out config and migration cache to allow config overrides to take
    // effect.
    if ($entity->bundle() == 'stanford_events_importer') {
      \Drupal::service('plugin.manager.migration')->clearCachedDefinitions();
      Cache::invalidateTags([
        'config:migrate_plus.migration.stanford_localist_importer',
        'migration_plugins',
      ]);
    }
  }

}
