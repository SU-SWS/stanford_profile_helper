<?php

/**
 * @file
 * stanford_intranet.post_update.php
 */

/**
 * Rebuild node access so author grants no longer allow update and delete.
 */
function stanford_intranet_post_update_rebuild_author_grants(&$sandbox) {
  if (!\Drupal::state()->get('stanford_intranet', FALSE)) {
    return;
  }
  node_access_rebuild(TRUE);
}
