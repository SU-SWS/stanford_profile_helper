<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Service;

use Drupal\ui_patterns_legacy\Service\ConfigurationUpdater;

/**
 * UI Patterns configuration updater that skips configuration migration.
 *
 * @codeCoverageIgnore
 */
class UiPatternsConfigurationUpdater extends ConfigurationUpdater {

  /**
   * {@inheritdoc}
   */
  public function migrateConfiguration(string $filter = '*'): void {}

}
