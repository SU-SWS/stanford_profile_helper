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
 * Implements hook_post_update_NAME().
 */
function stanford_profile_helper_post_update_8001() {
  \Drupal::state()->delete('stanford_profile_allow_all_paragraphs');

  $node_storage = \Drupal::entityTypeManager()->getStorage('node');
  $existing = $node_storage->loadByProperties(['title' => 'Publications']);
  // If a publications node already exists, leave it be.
  if (empty($existing)) {
    $node_storage->create([
      'type' => 'stanford_page',
      'title' => 'Publications',
      'uuid' => 'ce9cb7ca-6c59-4eea-9934-0a33057a7ff2',
    ])->save();
  }
}
