<?php

declare(strict_types=1);

namespace Drupal\stanford_layout_paragraphs\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\ViewExecutable;

/**
 * Hooks that alter the layout paragraphs editing experience.
 */
class LayoutParagraphsHooks {

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension): void {
    if ($extension == 'layout_paragraphs') {
      $libraries['builder']['dependencies'][] = 'stanford_layout_paragraphs/layout_paragraphs';
    }
  }

  /**
   * Implements hook_preprocess().
   */
  #[Hook('preprocess')]
  public function preprocess(&$variables, $hook): void {
    // If not editing LP page, we don't want to do anything.
    if (!$this->isEditingLayoutParagraphs()) {
      return;
    }

    // Remove any contextual links on the media/paragraphs/other entities when
    // editing the layout paragraphs page.
    if (!empty($variables['elements']['#entity_type'])) {
      unset($variables['title_suffix']['contextual_links']);
    }
  }

  /**
   * Is the user currently on editing the layout paragraphs?
   *
   * @return bool
   *   True if the route matches known routes for LP.
   */
  protected function isEditingLayoutParagraphs(): bool {
    $layout_paragraphs_routes = ['entity.node.edit_form'];
    $route_name = \Drupal::routeMatch()->getRouteName();
    return $route_name && (in_array($route_name, $layout_paragraphs_routes) || str_starts_with($route_name, 'layout_paragraphs.'));
  }

  /**
   * Implements hook_paragraphs_behavior_info_alter().
   */
  #[Hook('paragraphs_behavior_info_alter')]
  public function paragraphsBehaviorInfoAlter(&$paragraphs_behavior): void {
    $paragraphs_behavior['layout_paragraphs']['class'] = 'Drupal\stanford_layout_paragraphs\Plugin\paragraphs\Behavior\LayoutParagraphs';
  }

  /**
   * Implements hook_views_pre_execute().
   */
  #[Hook('views_pre_execute')]
  public function viewsPreExecute(ViewExecutable $view): void {
    $excluded_views = ['media_library'];
    if (
      $this->isEditingLayoutParagraphs() &&
      !in_array($view->id(), $excluded_views)
    ) {
      $current_limit = $view->query->getLimit();
      if ($current_limit <= 0 || $current_limit > 5) {
        $view->query->setLimit(6);
      }
    }
  }

}
