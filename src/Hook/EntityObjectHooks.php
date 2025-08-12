<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\stanford_profile_helper\StanfordProfileHelper;

/**
 * Entity event subscriber service.
 */
class EntityObjectHooks {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * Before saving a field storage, adjust the third party settings.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field_storage
   *   Field storage being saved.
   */
  #[Hook('field_storage_config_presave')]
  public static function preSaveFieldStorageConfig(FieldStorageConfigInterface $field_storage): void {
    // If a field is saved and the field permissions are public, lets just
    // remove those third party settings before save so that it keeps the
    // config clean.
    if ($field_storage->getThirdPartySetting('field_permissions', 'permission_type') === 'public') {
      $field_storage->unsetThirdPartySetting('field_permissions', 'permission_type');
      $field_storage->calculateDependencies();
    }
  }

  /**
   * When saving/deleting a menu item, clear caches.
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $entity
   *   Menu item being saved.
   */
  #[Hook('menu_link_content_insert')]
  #[Hook('menu_link_content_delete')]
  public function insertMenuLinkContent(MenuLinkContentInterface $entity): void {
    StanfordProfileHelper::clearMenuCacheTag();
  }

  /**
   * When updating a menu item, clear caches if necessary.
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $entity
   *   Modified menu item.
   */
  #[Hook('menu_link_content_update')]
  public function updateMenuLinkContent(MenuLinkContentInterface $entity): void {
    $original_entity = $entity->getOriginal();
    $compare_fields = ['title', 'link', 'parent', 'weight', 'expanded'];
    $original = $updated = [];
    foreach ($compare_fields as $field_name) {
      $original[] = $original_entity->get($field_name)->getValue();
      $updated[] = $entity->get($field_name)->getValue();
    }
    if (md5(json_encode($original)) != md5(json_encode($updated))) {
      StanfordProfileHelper::clearMenuCacheTag();
    }
  }

  /**
   * Before saving a menu item, adjust the path if an internal path exists.
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $entity
   *   The menu link being saved.
   */
  #[Hook('menu_link_content_presave')]
  public function preSaveMenuLinkContent(MenuLinkContentInterface $entity): void {
    $destination = $entity->get('link')->getString();
    if ($internal_path = self::lookupInternalPath($destination)) {
      $entity->set('link', $internal_path);
    }

    // For new menu link items created on a node form (normally), set the
    // expanded attribute so all menu items are expanded by default.
    $expanded = $entity->isNew() ?: $entity->isExpanded();
    $entity->set('expanded', $expanded);
    /** @var \Drupal\Core\Menu\MenuLinkManagerInterface $link_manager */
    $link_manager = \Drupal::service('plugin.manager.menu.link');
    $parent_ids = $link_manager->getParentIds($entity->getPluginId()) ?: [];

    $cache_tags = [];
    // When a menu item is added as a child of another menu item clear the
    // parent pages cache so that the block shows up as it doesn't get
    // invalidated just by the menu cache tags.
    foreach ($parent_ids as $parent_id) {
      $link = $link_manager->getDefinition($parent_id);
      if (isset($link['route_parameters']['node'])) {
        $cache_tags[] = 'node:' . $link['route_parameters']['node'];
      }
    }

    Cache::invalidateTags($cache_tags);
  }

  /**
   * Before saving a redirect, adjust the path if an internal path exists.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Redirect to be saved.
   */
  #[Hook('redirect_presave')]
  public function preSaveRedirect(ContentEntityInterface $entity): void {
    $destination = $entity->get('redirect_redirect')->getString();
    if ($internal_path = self::lookupInternalPath($destination)) {
      $entity->set('redirect_redirect', $internal_path);
    }
  }

  /**
   * Lookup an internal path.
   *
   * @param string $uri
   *   The destination path.
   *
   * @return string|null
   *   The internal path, or NULL if not found.
   */
  protected static function lookupInternalPath(string $uri): ?string {
    // If a redirect is added to go to the aliased path of a node (often from
    // importing redirect), change the destination to target the node instead.
    // This works if the destination is `/about` or `/node/9`.
    if (preg_match('/^internal:(\/.*)/', $uri, $matches)) {
      // Find the internal path from the alias.
      $path = \Drupal::service('path_alias.manager')
        ->getPathByAlias($matches[1]);

      // Grab the node id from the internal path and use that as destination.
      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        return 'entity:node/' . $matches[1];
      }
    }
    return NULL;
  }

}
