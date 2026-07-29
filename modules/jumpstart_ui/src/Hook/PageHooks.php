<?php

declare(strict_types=1);

namespace Drupal\jumpstart_ui\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\AdminContext;

/**
 * Hooks that are at the page level.
 */
class PageHooks {

  /**
   * Page hook constructor.
   *
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   Admin context service.
   */
  public function __construct(protected AdminContext $adminContext) {}

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$page): void {
    // It is recommended that you don't just add a library to all pages but
    // rather, conditionally require this library only where it is needed.
    // See: https://www.drupal.org/node/2274843
    // Only add on non-admin pages.
    if ($this->adminContext->isAdminRoute() == FALSE) {
      $page['#attached']['library'][] = 'jumpstart_ui/base';
      $page['#attached']['library'][] = 'jumpstart_ui/layout';
      $page['#attached']['library'][] = 'jumpstart_ui/jumpstart_ui';
    }
  }

}
