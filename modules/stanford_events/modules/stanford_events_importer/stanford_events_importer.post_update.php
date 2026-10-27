<?php

/**
 * @file
 * stanford_events_importer.post_update.php
 */

/**
 * Use the Localist source plugin to build the events importer urls.
 */
function stanford_events_importer_post_update_8001() {
  $config = \Drupal::configFactory()
    ->getEditable('migrate_plus.migration.stanford_localist_importer');
  if (!$config->isNew() && $config->get('source.plugin') == 'url') {
    $config->set('source.plugin', 'localist_url')->save();
    \Drupal::service('plugin.manager.migration')->clearCachedDefinitions();
  }
}
