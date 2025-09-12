<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;

/**
 * Provides an interface defining a WordPress migration entity type.
 */
interface WordPressMigrationInterface extends ContentEntityInterface, EntityPublishedInterface {

  /**
   * Enables the configuration entity.
   *
   * @return $this
   */
  public function enable(): self;

  /**
   * Disables the configuration entity.
   *
   * @return $this
   */
  public function disable(): self;

  /**
   * Get the configured base url of the WordPress site.
   *
   * @return string|NULL
   *   Site domain.
   */
  public function getBaseUrl(): ?string;

  /**
   * Get the associative array of configuration from the entity.
   */
  public function getConfiguration(): array;

  /**
   * Set a value in the configuration.
   *
   * @param string|array $key
   *   Parent path of the configuration.
   * @param mixed $value
   *  Value to set the configuration.
   */
  public function setConfigurationValue(string|array $key, $value): void;

  /**
   * Get a value in the configuration.
   *
   * @param string|array $key
   *   Parent path of the desired value in the configuration.
   * @param mixed $default_value
   *   Default value if no value found.
   *
   * @return mixed
   *   Value of the configuration.
   */
  public function getConfigurationValue(string|array $key, $default_value = NULL);

}
