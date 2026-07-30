<?php

namespace Drupal\stanford_intranet\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ConfigFactoryOverrideInterface;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\State\StateInterface;

/**
 * Class ConfigOverrider.
 *
 * @package Drupal\stanford_intranet\Config
 */
class ConfigOverrider implements ConfigFactoryOverrideInterface {

  /**
   * Config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Core state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * ConfigOverrider constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   Config factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, StateInterface $state) {
    $this->configFactory = $config_factory;
    $this->state = $state;
  }

  /**
   * {@inheritDoc}
   */
  public function loadOverrides($names) {
    $overrides = [];

    if (!$this->state->get('stanford_intranet', FALSE)) {
      return $overrides;
    }

    // The state will be `TRUE` if allowing file uploads, still change the
    // upload scheme. But if we want to make the files public, no need to change
    // the config.
    if ($this->state->get('stanford_intranet.allow_file_uploads') != 'public') {
      $overrides['system.file']['default_scheme'] = 'private';
      foreach ($names as $name) {
        if (str_starts_with($name, 'field.storage.')) {
          $scheme = $this->configFactory->getEditable($name)
            ->getOriginal('settings.uri_scheme', FALSE);
          // If the field isn't a file or image field, it won't have an upload
          // scheme.
          if ($scheme == 'public') {
            $overrides[$name]['settings']['uri_scheme'] = 'private';
          }
        }
      }
    }

    // Don't alert document links on intranet sites.
    if (in_array('editoria11y.settings', $names)) {
      $overrides['editoria11y.settings']['download_links'] = "a[href$='.ppt']";
    }

    if (in_array('r4032login.settings', $names)) {
      $overrides['r4032login.settings']['display_denied_message'] = FALSE;
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
    return 'stanford_intranet';
  }

  /**
   * {@inheritDoc}
   */
  public function getCacheableMetadata($name) {
    return new CacheableMetadata();
  }

}
