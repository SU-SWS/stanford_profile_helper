<?php

declare(strict_types=1);

namespace Drupal\stanford_person\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;

/**
 * Hooks that manage custom views cache tags for person content.
 */
class ViewsCacheHooks {

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
   * @see self::nodePresave()
   * @see self::taxonomyTermPresave()
   */
  #[Hook('views_post_render')]
  public function viewsPostRender(ViewExecutable $view, &$output, CachePluginBase $cache): void {

    // Node Base Table Views.
    switch ($view->id()) {
      case 'stanford_person':
        $node_list_position = array_search('node_list', $output['#cache']['tags']);
        unset($output['#cache']['tags'][$node_list_position]);
        foreach ($view->filter['type']->value as $node_type) {
          $output['#cache']['tags'][] = "node_list:$node_type";
        }
        $output['#attached']['library'][] = 'stanford_person/views';
        break;

      case 'taxonomy_term_pages':
        if ($view->current_display == 'people_terms') {
          $output['#attached']['library'][] = 'stanford_person/views';
        }
        break;

      case 'stanford_person_list_terms_first':
        $output['#attached']['library'][] = 'stanford_person/views';

      case 'stanford_person_terms':
        $term_list_position = array_search('term_list', $output['#cache']['tags']);
        unset($output['#cache']['tags'][$term_list_position]);
        foreach ($view->filter['vid']->value as $term_type) {
          $output['#cache']['tags'][] = "term_list:$term_type";
        }
        break;
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   *
   * @see self::viewsPostRender()
   */
  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $entity): void {
    if ($entity->bundle() == "stanford_person") {
      Cache::invalidateTags(["node_list:{$entity->bundle()}"]);
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   *
   * @see self::viewsPostRender()
   */
  #[Hook('taxonomy_term_presave')]
  public function taxonomyTermPresave(TermInterface $entity): void {
    if ($entity->bundle() == "stanford_person_types") {
      Cache::invalidateTags(["term_list:{$entity->bundle()}"]);
    }
  }

}
