<?php

namespace Drupal\stanford_decoupled\Config;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Config overrides when a site is decoupled.
 */
class DecoupledConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Config override constructor.
   */
  public function __construct() {}

  /**
   * {@inheritDoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    foreach ($names as $name) {
      if (str_starts_with($name, 'filter.format.') && self::isDecoupled()) {
        $overrides[$name]['filters']['filter_responsive_tables_filter']['status'] = FALSE;
      }
    }

    return $overrides;
  }

  /**
   * {@inheritDoc}
   */
  public function createConfigObject($name, $collection = StorageInterface::DEFAULT_COLLECTION) {
    return NULL;
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheSuffix() {
    return 'StanfordDecoupled';
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * Check if any Next module configs exist to determine if the site is
   * decoupled.
   *
   * @return bool
   *   If the site is decoupled.
   */
  public static function isDecoupled(): bool {
    $cache = \Drupal::cache();
    if ($cache_data = $cache->get('stanford_decoupled')) {
      return $cache_data->data;
    }

    $entity_type_manager = \Drupal::entityTypeManager();
    if (!$entity_type_manager->hasDefinition('next_entity_type_config')) {
      $cache->set('stanford_decoupled', FALSE, Cache::PERMANENT, ['config:next_entity_type_config_list']);
      return FALSE;
    }

    $is_decoupled = !!$entity_type_manager->getStorage('next_entity_type_config')
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    $cache->set('stanford_decoupled', $is_decoupled, Cache::PERMANENT, ['config:next_entity_type_config_list']);
    return $is_decoupled;
  }

}
