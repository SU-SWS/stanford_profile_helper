<?php

declare(strict_types=1);

namespace Drupal\stanford_events_series\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;

/**
 * Node hooks for stanford_events_series.
 */
class NodeHooks {

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $entity): void {
    if ($entity->bundle() != "stanford_event_series") {
      return;
    }

    // If an event series item is being edited or saved,
    // clear out some cache tags.
    Cache::invalidateTags(["node_list:{$entity->bundle()}"]);
  }

}
