<?php

/**
 * @file
 * stanford_profile_helper.post_update.php
 */

use Drupal\block_content\Entity\BlockContent;
use Drupal\Core\Url;

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

/**
 * Set the link title for media caption paragraph link fields.
 */
function stanford_profile_helper_post_update_8100() {
  $paragraph_storage = \Drupal::entityTypeManager()
    ->getStorage('paragraph');
  $entity_ids = $paragraph_storage->getQuery()
    ->accessCheck(FALSE)
    ->condition('type', 'stanford_media_caption')
    ->exists('su_media_caption_link')
    ->execute();

  foreach ($paragraph_storage->loadMultiple($entity_ids) as $paragraph) {
    $value = $paragraph->get('su_media_caption_link')->get(0)->getValue();
    $link_url = $value['uri'];

    $title = NULL;
    try {
      $url = Url::fromUri($link_url);
      if ($url->isRouted()) {
        // The only routed urls the link field supports is to nodes.
        $parameters = $url->getRouteParameters();
        $node = \Drupal::entityTypeManager()
          ->getStorage('node')
          ->load($parameters['node']);
        $title = $node ? $node->label() : NULL;
      }

      // Absolute external url, fetch the contents of the page and grab the
      // `<title>` value
      if ($url->isExternal()) {
        $page = file_get_contents($url->toString());
        $title = preg_match('/<title[^>]*>(.*?)<\/title>/ims', $page, $match) ? $match[1] : NULL;
      }

      // If no title is found above, use the last part of the url as a sensible
      // default to try an establish an human readable title.
      if (!$title) {
        $title = substr($url->toString(), strrpos($url->toString(), '/') + 1);
        $title = ucwords(preg_replace('/[^\da-z]/i', ' ', $title));

        // If STILL no title, throw an error to trigger the logger in the catch.
        if (!$title) {
          throw new \Exception('Trigger log');
        }
      }
    } catch (\Exception $e) {
      \Drupal::logger('stanford_profile_helper')
        ->error('Unable to set link title for paragraph %id with url %url', [
          '%id' => $paragraph->id(),
          '%url' => $link_url,
        ]);
      continue;
    }

    if ($title) {
      $value['title'] = $title;
      $paragraph->set('su_media_caption_link', [$value])->save();
    }
  }
}
