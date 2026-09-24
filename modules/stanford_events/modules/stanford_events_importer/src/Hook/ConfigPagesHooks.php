<?php

declare(strict_types=1);

namespace Drupal\stanford_events_importer\Hook;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\config_pages\ConfigPagesInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Config pages hooks for stanford_events_importer.
 */
class ConfigPagesHooks {

  /**
   * Config pages hooks constructor.
   *
   * @param \Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface $migrationManager
   *   Migration plugin manager service.
   */
  public function __construct(
    #[Autowire(service: 'plugin.manager.migration')]
    protected CachedDiscoveryInterface $migrationManager,
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('config_pages_presave')]
  public function configPagesPresave(ConfigPagesInterface $entity): void {
    // Clear out config and migration cache to allow config overrides to take
    // effect.
    if ($entity->bundle() == 'stanford_events_importer') {
      $this->migrationManager->clearCachedDefinitions();
      Cache::invalidateTags([
        'config:migrate_plus.migration.stanford_localist_importer',
        'migration_plugins',
      ]);
    }
  }

}
