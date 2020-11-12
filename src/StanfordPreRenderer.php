<?php

namespace Drupal\stanford_profile_helper;

use Drupal\Core\Security\TrustedCallbackInterface;

/**
 * Class StanfordPreRenderer.
 *
 * @package Drupal\stanford_profile_helper
 */
class StanfordPreRenderer implements TrustedCallbackInterface {

  /**
   * {@inheritDoc}
   */
  public static function trustedCallbacks(): array {
    return ['preRenderDsEntity'];
  }

  /**
   * PreRender the ds entity to add contextual links.
   *
   * @param array $element
   *   Entity render array.
   *
   * @return array
   *   Altered render array.
   */
  public static function preRenderDsEntity(array $element): array {
    $module_handler = \Drupal::moduleHandler();
    if (isset($element['#contextual_links']) && $module_handler->moduleExists('contextual')) {
      $placeholder = [
        '#type' => 'contextual_links_placeholder',
        '#id' => _contextual_links_to_id($element['#contextual_links']),
      ];
      $element['#prefix'] = render($placeholder);
    }
    return $element;
  }

}
