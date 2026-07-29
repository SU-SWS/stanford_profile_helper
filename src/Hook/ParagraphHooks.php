<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks that relate to paragraph entities.
 */
class ParagraphHooks {

  /**
   * Implements hook_contextual_links_alter().
   */
  #[Hook('contextual_links_alter')]
  public function contextualLinksAlter(array &$links, $group, array $route_parameters): void {
    if ($group == 'paragraph') {
      // Paragraphs edit module clone link does not function correctly.
      // Remove it from available links. Also remove delete to avoid
      // unwanted delete.
      unset($links['paragraphs_edit.delete_form']);
      unset($links['paragraphs_edit.clone_form']);
    }
  }

}
