<?php

/**
 * @file
 * stanford_profile_helper.post_update.php
 */

use Drupal\block_content\Entity\BlockContent;

/**
 * Create the events and news intro block content.
 */
function stanford_profile_helper_post_update_8000(&$sandbox) {
  BlockContent::create([
    'uuid' => 'f7125c85-197d-4ba2-9f6f-1126bbda0466',
    'type' => 'stanford_component_block',
    'info' => 'Events Intro',
  ])->save();
  BlockContent::create([
    'uuid' => '5168834f-3271-4951-bd95-e75340ca5522',
    'type' => 'stanford_component_block',
    'info' => 'News Intro',
  ])->save();
}

/**
 * Clear out the state that limits the paragraph types.
 */
function stanford_profile_helper_post_update_8001() {
  \Drupal::state()->delete('stanford_profile_allow_all_paragraphs');
}
