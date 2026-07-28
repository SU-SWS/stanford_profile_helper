<?php

declare(strict_types=1);

namespace Drupal\stanford_events\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;

/**
 * Views hooks for stanford_events.
 */
class ViewsHooks {

  /**
   * Implements hook_views_post_render().
   *
   * Views render arrays contain a cache tag "node_list". This cache tag is
   * cleared every time ANY node is created, edited or deleted. When this happens
   * every view on the site gets its cache flushed. This causes poor performance
   * since a view would get flushed even if it has no relation to that node. To
   * assist in cache tags, we create a custom cache tag based on the node type
   * filter on the view. Its a small improvement but will have huge impact in
   * keeping cached renders much longer.
   *
   * @see stanford_events_node_presave()
   * @see stanford_events_taxonomy_term_presave()
   */
  #[Hook('views_post_render')]
  public function viewsPostRender(ViewExecutable $view, array &$output, CachePluginBase $cache): void {

    $allow_list_node = [
      'stanford_events',
      'stanford_events_past',
      'stanford_events_schedule',
    ];

    $allow_list_term = [
      'stanford_event_terms_utility',
    ];

    $id = $view->id();

    // Node Base Table Views.
    if (in_array($id, $allow_list_node)) {
      $output['#attached']['library'][] = 'stanford_events/event_views';

      $node_list_position = array_search('node_list', $output['#cache']['tags']);
      unset($output['#cache']['tags'][$node_list_position]);
      foreach ($view->filter['type']->value as $node_type) {
        $output['#cache']['tags'][] = "node_list:$node_type";
      }
    }

    // Term Base Table Views.
    if (in_array($id, $allow_list_term)) {
      $output['#attached']['library'][] = 'stanford_events/event_views';

      $term_list_position = array_search('term_list', $output['#cache']['tags']);
      unset($output['#cache']['tags'][$term_list_position]);
      foreach ($view->filter['vid']->value as $term_type) {
        $output['#cache']['tags'][] = "term_list:$term_type";
      }
    }

  }

}
