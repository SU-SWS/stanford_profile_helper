<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * WordPress Migration hooks.
 */
class StanfordWordPressMigrateHooks {

  /**
   * Implements hook_entity_type_alter().
   *
   * Add routes for devel module.
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) {
    if ($entity_types['wordpress_migration']->hasLinkTemplate('devel-load')) {
      $entity_types['wordpress_migration']->setLinkTemplate('devel-load', '/devel/wordpress-migration/{wordpress_migration}');
    }
    if ($entity_types['wordpress_migration']->hasLinkTemplate('devel-definition')) {
      $entity_types['wordpress_migration']->setLinkTemplate('devel-definition', '/devel/definition/wordpress-migration/{wordpress_migration}');
    }
    if ($entity_types['wordpress_migration']->hasLinkTemplate('devel-load-with-references')) {
      $entity_types['wordpress_migration']->setLinkTemplate('devel-load-with-references', '/devel/load-with-references/wordpress-migration/{wordpress_migration}/');
    }
    if ($entity_types['wordpress_migration']->hasLinkTemplate('devel-path-alias')) {
      $entity_types['wordpress_migration']->setLinkTemplate('devel-path-alias', '/devel/path/alias/wordpress-migration/{wordpress_migration}');
    }

  }

}
