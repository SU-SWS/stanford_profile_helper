<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks that relate to entity view display rendering.
 */
class DisplayHooks {

  /**
   * Implements hook_entity_view_display_alter().
   */
  #[Hook('entity_view_display_alter')]
  public function entityViewDisplayAlter(EntityViewDisplayInterface $display, array $context): void {
    if (str_contains($context['view_mode'], 'search_indexing') && $context['entity_type'] == 'node') {
      // The title is already in the template, it's not needed in the
      // display.
      $display->removeComponent('title');
    }
  }

}
