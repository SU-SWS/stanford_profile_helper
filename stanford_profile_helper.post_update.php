<?php

/**
 * @file
 * stanford_profile_helper.post_update.php
 */

/**
 * Implements hook_removed_post_updates().
 */
function stanford_profile_helper_removed_post_updates() {
  return [
    'stanford_profile_helper_post_update_8000' => '9.4.3',
    'stanford_profile_helper_post_update_8001' => '9.4.3',
    'stanford_profile_helper_post_update_8100' => '9.4.3',
    'stanford_profile_helper_post_update_8101' => '9.4.3',
    'stanford_profile_helper_post_update_8102' => '9.4.3',
    'stanford_profile_helper_post_update_8103' => '9.4.3',
    'stanford_profile_helper_post_update_9000' => '9.4.3',
    'stanford_profile_helper_post_update_9001' => '9.4.3',
    'stanford_profile_helper_post_update_create_cron' => '9.4.3',
  ];
}

/**
 * Clear items from Algolia that are in the trash.
 */
function stanford_profile_helper_post_update_algolia_trash_delete(&$sandbox) {
  $sapi_config = \Drupal::config('search_api.server.algolia_search');
  if (!$sapi_config->get('backend_config.api_key') || $sapi_config->get('read_only')) {
    return;
  }
  $trash_manager = \Drupal::service('trash.manager');
  $trash_manager->setTrashContext('ignore');
  $ns = \Drupal::entityTypeManager()
    ->getStorage('node');
  $nids = $ns->getQuery()
    ->accessCheck(FALSE)
    ->condition('deleted', 1, '>')
    ->execute();
  foreach ($ns->loadMultiple($nids) as $node) {
    \Drupal::service('search_api_algolia.helper')->entityDelete($node);
  }
  $trash_manager->setTrashContext('active');
}
