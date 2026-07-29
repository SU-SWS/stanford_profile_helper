<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Database\Connection;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\taxonomy\TermInterface;

/**
 * Hooks that relate to taxonomy terms.
 */
class TaxonomyHooks {

  /**
   * Taxonomy hook constructor.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $routeBuilder
   *   Router builder service.
   */
  public function __construct(protected Connection $database, protected RouteBuilderInterface $routeBuilder) {}

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('taxonomy_term_update')]
  public function taxonomyTermUpdate(TermInterface $entity): void {
    // https://www.drupal.org/project/taxonomy_menu/issues/2867626
    $original_parent = $entity->getOriginal()->get('parent')->getString();
    if ($original_parent == $entity->get('parent')->getString()) {
      return;
    }
    $menu_link_exists = $this->database->select('menu_tree', 'm')->fields('m')
      ->condition('id', 'taxonomy_menu.menu_link%', 'LIKE')
      ->condition('route_param_key', 'taxonomy_term=' . $entity->id())
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($menu_link_exists > 0) {
      $this->database->delete('menu_tree')
        ->condition('id', 'taxonomy_menu.menu_link%', 'LIKE')
        ->condition('route_param_key', 'taxonomy_term=' . $entity->id())
        ->execute();
      $this->routeBuilder->rebuild();
    }
  }

}
