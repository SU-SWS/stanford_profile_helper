<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_styles\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\ViewExecutable;

/**
 * Views hooks.
 */
class ViewsHooks {

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {
    if ($view->id() == 'media_content') {
      $view->element['#attached']['library'][] = 'core/drupal.dialog.ajax';
    }

    if ($view->id() == 'search') {
      $view->element['#attached']['library'][] = 'stanford_profile_styles/views.search';
    }

    if ($view->storage->id() == 'taxonomy_term_pages') {
      $view->element['#attached']['library'][] = 'stanford_person/views';
    }
  }

}
