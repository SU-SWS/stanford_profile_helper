<?php

declare(strict_types=1);

namespace Drupal\jumpstart_ui\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks related to media entity rendering.
 */
class MediaHooks {

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_media')]
  public function preprocessMedia(&$variables): void {
    $variables['attributes']['class'][] = 'media-entity-wrapper';
    $variables['attributes']['class'][] = $variables['elements']['#media']->bundle();
  }

}
