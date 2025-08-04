<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\user\RoleInterface;
use Drupal\user\UserInterface;

/**
 * Hooks that act on users or roles.
 */
class UserHooks {

  public function __construct(protected ModuleHandlerInterface $moduleHandler, protected EntityTypeManagerInterface $entityTypeManager, protected ConfigFactoryInterface $configFactory) {}

  /**
   * Update samlauth settings when a user role is created or deleted.
   *
   * @param \Drupal\user\RoleInterface $role
   *   Role entity.
   */
  #[Hook('user_role_insert')]
  #[Hook('user_role_delete')]
  public function userRoleInsert(RoleInterface $role) {
    if (!$this->moduleHandler->moduleExists('samlauth')) {
      return;
    }

    $role_ids = array_keys($this->entityTypeManager->getStorage('user_role')
      ->loadMultiple());
    $role_ids = array_combine($role_ids, $role_ids);
    unset($role_ids[RoleInterface::AUTHENTICATED_ID]);
    asort($role_ids);

    $config = $this->configFactory->getEditable('samlauth.authentication');
    $config->set('map_users_roles', $role_ids)->save();
  }

  /**
   * Before saving a user role, prepend it with `custm_`.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The role being saved.
   */
  #[Hook('user_role_presave')]
  public function userRolePreSave(RoleInterface $role) {
    // Only modify new roles if they are created through the UI and don't exist
    // in the config management - Prefix them with "custm_" so they can be
    // easily identifiable.
    if (PHP_SAPI != 'cli' && $role->isNew()) {
      $role->set('id', 'custm_' . $role->id());
    }
  }

  /**
   * Clear renewal date cache when a user is created or edited (role changed?).
   *
   * @param \Drupal\user\UserInterface $user
   *   User entity.
   */
  #[Hook('user_presave')]
  public function userPreSave(UserInterface $user) {
    // Invalidate the site renewal redirect logic in case the user now has
    // permissions to make the needed changes.
    Cache::invalidateTags(['site-renew-date']);
  }

}
