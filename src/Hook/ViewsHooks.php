<?php

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\ViewExecutable;

/**
 * Views hook event subscriber.
 */
class ViewsHooks {

  /**
   * Modify the cache tags on views..
   */
  #[Hook('views_pre_view')]
  public function viewsPreView(ViewExecutable $view, $display_id, array &$args) {
    $display_options = &$view->getDisplay()->options;

    // When viewing the "default" view display, just escape out.
    if (!isset($view->getDisplay()->default_display)) {
      return;
    }

    $default_options = &$view->getDisplay()->default_display->options;
    $filters = !empty($display_options['filters']) ? $display_options['filters'] : $default_options['filters'];

    // Change the default cache mechanism to use custom tags that we generate
    // using the node type filters that exist on the view.
    // @see \Drupal\Core\Entity\EntityBase::getListCacheTagsToInvalidate().
    if (!empty($filters['type']['entity_type']) && $filters['type']['entity_type'] == 'node') {

      $tags = [];
      foreach ($filters['type']['value'] as $node_type) {
        $tags[] = 'node_list:' . $node_type;
      }

      // If no node type tags are available, fall back to general `node_list`.
      $tags = empty($tags) ? ['node_list'] : $tags;
      $cache = [
        'type' => 'custom_tag',
        'options' => ['custom_tag' => implode(' ', $tags)],
      ];
      $display_options['cache'] = $cache;
      $default_options['cache'] = $cache;
    }
  }

}
