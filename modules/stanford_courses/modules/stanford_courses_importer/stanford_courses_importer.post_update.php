<?php

/**
 * @file
 * stanford_courses_importer.post_update.php
 */

/**
 * Use the ExploreCourses source plugin to build the course importer urls.
 */
function stanford_courses_importer_post_update_8001() {
  $config_factory = \Drupal::configFactory();
  $migration = $config_factory->getEditable('migrate_plus.migration.stanford_courses');
  if (!$migration->isNew() && $migration->get('source.plugin') == 'url') {
    $migration->set('source.plugin', 'explore_courses_url')->save();
  }

  // The source plugin provides the urls, so the config page no longer
  // overrides them.
  $page_type = $config_factory->getEditable('config_pages.type.stanford_courses_importer');
  $overrides = $page_type->get('third_party_settings.config_pages_overrides') ?: [];
  $kept = array_filter($overrides, fn($override) => !($override['config_name'] == 'migrate_plus.migration.stanford_courses' && $override['config_item'] == 'source.urls'));
  if (count($kept) != count($overrides)) {
    $page_type->set('third_party_settings.config_pages_overrides', $kept)->save();
  }
  \Drupal::service('plugin.manager.migration')->clearCachedDefinitions();
}
