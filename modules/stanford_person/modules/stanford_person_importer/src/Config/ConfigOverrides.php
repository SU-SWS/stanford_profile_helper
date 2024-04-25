<?php

namespace Drupal\stanford_person_importer\Config;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;

/**
 * Configuration overrides for stanford person importer migration entity.
 *
 * @package Drupal\stanford_person_importer\Config
 */
class ConfigOverrides implements ConfigFactoryOverrideInterface {

  /**
   * Config pages loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface
   */
  protected $configPages;

  /**
   * ConfigOverrides constructor.
   *
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $config_pages
   *   Config pages loader service.
   */
  public function __construct(protected ConfigPagesLoaderServiceInterface $config_pages) {
    $this->configPages = $config_pages;
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
    return 'StanfordPersonImporterConfigOverride';
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

  /**
   * {@inheritDoc}
   */
  public function loadOverrides($names) {
    $overrides = [];
    if (in_array('migrate_plus.migration.su_stanford_person', $names)) {
      $overrides['migrate_plus.migration.su_stanford_person']['source']['plugin'] = 'cap_url';
      $overrides['migrate_plus.migration.su_stanford_person']['source']['authentication']['client_id'] = $this->getCapClientId();
      $overrides['migrate_plus.migration.su_stanford_person']['source']['authentication']['client_secret'] = $this->getCapClientSecret();
    }
    return $overrides;
  }

  /**
   * Get the username from the config pages field.
   *
   * @return string
   *   Client ID string.
   */
  protected function getCapClientId(): string {
    return $this->configPages->getValue('stanford_person_importer', 'su_person_cap_username', 0, 'value') ?? '';
  }

  /**
   * Get the password from the config pages field.
   *
   * @return string
   *   Client secret string.
   */
  protected function getCapClientSecret(): string {
    return $this->configPages->getValue('stanford_person_importer', 'su_person_cap_password', 0, 'value') ?? '';
  }

}
