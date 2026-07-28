<?php

declare(strict_types=1);

namespace Drupal\stanford_courses\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\ViewExecutable;

/**
 * Views hooks for stanford_courses.
 */
class ViewsHooks {

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {
    if ($view->storage->id() == 'stanford_courses') {
      $view->element['#attached']['library'][] = 'stanford_courses/courses_page';
    }
  }

}
