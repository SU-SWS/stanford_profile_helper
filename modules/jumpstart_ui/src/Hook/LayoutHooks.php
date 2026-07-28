<?php

declare(strict_types=1);

namespace Drupal\jumpstart_ui\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Hooks related to layout rendering.
 */
class LayoutHooks {

  /**
   * Layout hook constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Current route match service.
   */
  public function __construct(protected RouteMatchInterface $routeMatch) {}

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_layout')]
  public function preprocessLayout(&$variables): void {
    $current_route = $this->routeMatch->getRouteName();
    if (str_starts_with($current_route, 'layout_builder.')) {
      // Add a flag if the user is currently in layout builder. This allows the
      // template to make it easier for users to move blocks in layout builder.
      $variables['layout_builder_admin'] = TRUE;
    }
  }

}
