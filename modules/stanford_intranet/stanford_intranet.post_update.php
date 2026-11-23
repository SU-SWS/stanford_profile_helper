<?php

/**
 * @file
 * stanford_intranet.post_update.php
 */

use Drupal\Component\Utility\DeprecationHelper;
use Drupal\node\NodeAccessRebuild;

/**
 * Rebuild node access so author grants no longer allow update and delete.
 */
function stanford_intranet_post_update_rebuild_author_grants(&$sandbox) {
  if (!\Drupal::state()->get('stanford_intranet', FALSE)) {
    return;
  }
  DeprecationHelper::backwardsCompatibleCall(
    \Drupal::VERSION,
    '11.4',
    fn() => \Drupal::service(NodeAccessRebuild::class)->rebuild(TRUE),
    fn() => node_access_rebuild(TRUE),
  );
}
