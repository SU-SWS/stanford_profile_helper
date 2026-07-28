<?php

namespace Drupal\stanford_profile_helper;

use Drupal\Core\Cache\Cache;
use Drupal\stanford_profile_helper\Event\MenuCacheEvent;
use Drupal\user\RoleInterface;

/**
 * Module helper methods and service.
 */
class StanfordProfileHelper {

  /**
   * Remove some cache tags from a render array.
   *
   * @param array|mixed $item
   *   Render array.
   * @param array $tags
   *   Cache tags to be removed from the render array using regex.
   */
  public static function removeCacheTags(&$item, array $tags = []) {
    if (!is_array($item) || empty($item['#cache']['tags'])) {
      return;
    }
    $item['#cache']['tags'] = array_filter($item['#cache']['tags'], function($tag) use ($tags) {
      foreach ($tags as $search_tag) {
        if (preg_match("/$search_tag/", $tag)) {
          return FALSE;
        }
      }
      return TRUE;
    });
    $item['#cache']['tags'] = array_values($item['#cache']['tags']);
  }

  /**
   * Clear the menu cache tags and dispatch an event.
   */
  public static function clearMenuCacheTag() {
    Cache::invalidateTags(['stanford_profile_helper:menu_links']);
    \Drupal::service('event_dispatcher')
      ->dispatch(new MenuCacheEvent(), MenuCacheEvent::CACHE_CLEARED);
  }

  /**
   * Get available roles, limited if the role_delegation module is enabled.
   *
   * @return array
   *   Keyed array of role id and role label.
   */
  public static function getAssignableRoles(): array {
    if (\Drupal::moduleHandler()->moduleExists('role_delegation')) {
      /** @var \Drupal\role_delegation\DelegatableRolesInterface $role_delegation */
      $role_delegation = \Drupal::service('delegatable_roles');
      return $role_delegation->getAssignableRoles(\Drupal::currentUser());
    }

    $roles = \Drupal::entityTypeManager()
      ->getStorage('user_role')
      ->loadMultiple();
    unset($roles[RoleInterface::ANONYMOUS_ID]);
    return array_map(fn($role) => $role->label(), $roles);
  }

}
