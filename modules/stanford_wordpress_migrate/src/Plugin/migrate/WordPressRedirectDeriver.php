<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\Plugin\migrate;

/**
 * WordPress Redirect importer deriver.
 */
class WordPressRedirectDeriver extends WordPressMigrationDeriverBase {

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    foreach ($this->getWordPressMigrations() as $migration) {
      foreach ($migration->getConfigurationValue('node', []) as $source => $destinations) {
        $sourceUrls = $this->getSourceUrls($migration->getBaseUrl() . "/wp-json$source");
        if (!$sourceUrls) {
          continue;
        }

        foreach ($destinations as $destination => $field_mapping) {
          $id = basename($source) . "__$destination";

          // If two migration settings map the same source to destination, make
          // sure we have separate migrations for each.
          if (isset($this->derivatives[$id])) {
            $id .= '-' . $migration->id();
          }

          $new_migration = $base_plugin_definition;
          $new_migration['source']['urls'] = $sourceUrls;
          $new_migration['migration_dependencies']['required'] = ['wordpress_content:' . $id];
          $new_migration['process']['redirect_source']['search'] = $migration->getBaseUrl();
          $this->derivatives[$id] = $new_migration;
        }
      }
    }

    return $this->derivatives;
  }

}
