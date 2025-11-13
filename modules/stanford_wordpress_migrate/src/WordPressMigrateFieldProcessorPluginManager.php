<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\stanford_wordpress_migrate\Attribute\WordPressMigrateFieldProcessor;

/**
 * WordPressMigrateFieldProcessor plugin manager.
 */
class WordPressMigrateFieldProcessorPluginManager extends DefaultPluginManager {

  /**
   * Constructs the object.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/WordPressMigrateFieldProcessor', $namespaces, $module_handler, WordPressMigrateFieldProcessorInterface::class, WordPressMigrateFieldProcessor::class);
    $this->alterInfo('wordpress_migrate_field_processor_info');
    $this->setCacheBackend($cache_backend, 'wordpress_migrate_field_processor_plugins');
  }

  /**
   * Get plugin instance
   *
   * @param string $fieldType
   *   Field type id.
   *
   * @return \Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorInterface|null
   *   Plugin instance if one fits.
   */
  public function getFieldPlugin(string $fieldType): ?WordPressMigrateFieldProcessorInterface {
    foreach ($this->getDefinitions() as $pluginId => $definition) {
      if (in_array($fieldType, $definition['fieldType'])) {
        return $this->createInstance($pluginId);
      }
    }
    return NULL;
  }

}
