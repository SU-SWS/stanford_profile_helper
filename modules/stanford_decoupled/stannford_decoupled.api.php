<?php

/**
 * @file
 * Decoupled hooks.
 */

use Drupal\next\Entity\NextSiteInterface;

/**
 * Alter the aggregated paths that will be revalidated.
 *
 * @param array $revalidations
 *   An array paths & tags to revalidate.
 * @param \Drupal\next\Entity\NextSiteInterface $site
 *   Site entity that is used for the revalidation.
 *
 * @codeCoverageIgnore
 */
function hook_next_site_revalidate_url_alter(array &$revalidations, NextSiteInterface $site) {
  $revalidations['paths'][] = '/foo/bar';
  $revalidations['tags'][] = 'bar:foo';
}
