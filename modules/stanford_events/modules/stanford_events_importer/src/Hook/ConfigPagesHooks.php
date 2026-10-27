<?php

declare(strict_types=1);

namespace Drupal\stanford_events_importer\Hook;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
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
    // The config page overrides the migration status, which decides if the
    // migration plugin exists, so the definitions have to be rebuilt.
    if ($entity->bundle() == 'stanford_events_importer') {
      $this->migrationManager->clearCachedDefinitions();
    }
  }

}
