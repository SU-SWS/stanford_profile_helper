<?php

declare(strict_types=1);

namespace Drupal\stanford_person\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;

/**
 * Hooks that attach libraries to stanford_person node pages.
 */
class PageAttachmentHooks {

  /**
   * Page attachment hooks constructor.
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
    if ($node instanceof NodeInterface && $node->getType() == 'stanford_person') {
      // Check for our type and add library if a match.
      $attachments['#attached']['library'][] = 'stanford_person/node';
    }
  }

}
