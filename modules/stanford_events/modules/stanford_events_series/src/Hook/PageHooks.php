<?php

declare(strict_types=1);

namespace Drupal\stanford_events_series\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;

/**
 * Page hooks for stanford_events_series.
 */
class PageHooks {

  /**
   * Page hooks constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Current route match service.
   */
  public function __construct(protected RouteMatchInterface $routeMatch) {}

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {

    // Get the node from the route.
    $node = $this->routeMatch->getParameter('node');

    // Not a node.. Then just continue.
    if (!$node instanceof NodeInterface || $node->bundle() != 'stanford_event_series') {
      return;
    }

    $attachments['#attached']['library'][] = 'stanford_events_series/event_series_node';
  }

}
