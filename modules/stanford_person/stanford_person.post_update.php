<?php

/**
 * @file
 * Post update functions for stanford_person.
 */

/**
 * Implements hook_removed_post_updates().
 */
function stanford_person_removed_post_updates() {
  return [
    'stanford_person_post_update_8100' => '11.0.0',
  ];
}
