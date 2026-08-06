<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;

/**
 * Hooks that relate to the config_readonly module.
 */
class ConfigReadonlyHooks {

  /**
   * Config readonly hook constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Current route match service.
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current active user.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   Messenger service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected RouteMatchInterface $routeMatch,
    protected AccountProxyInterface $currentUser,
    protected MessengerInterface $messenger,
  ) {}

  /**
   * Implements hook_config_readonly_whitelist_patterns().
   */
  #[Hook('config_readonly_whitelist_patterns')]
  public function configReadonlyWhitelistPatterns(): array {
    $default_theme = $this->configFactory->get('system.theme')->get('default');
    // Allow the theme settings to be changed in the UI.
    $patterns = ["$default_theme.settings"];

    // Allow the form to be submitted in the UI for specific routes that
    // don't alter the configuration, such as resetting the order of
    // taxonomy terms.
    $routes_to_config = [
      'entity.taxonomy_vocabulary.overview_form' => ['taxonomy.vocabulary.*'],
      'entity.search_api_index.rebuild_tracker' => ['search_api.index.*'],
      'entity.search_api_index.clear' => ['search_api.index.*'],
      'entity.search_api_index.reindex' => ['search_api.index.*'],
      'xmlsitemap.admin_rebuild' => ['xmlsitemap.settings'],
    ];

    $route_name = $this->routeMatch->getRouteName();
    if (isset($routes_to_config[$route_name])) {
      $patterns = [...$patterns, ...$routes_to_config[$route_name]];
    }
    return $patterns;
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_menu_edit_form_alter')]
  public function formMenuEditFormAlter(array &$form, $form_state): void {
    $read_only = Settings::get('config_readonly', FALSE);
    if (!$read_only) {
      return;
    }

    // If the form is locked, hide the config you cannot change from users
    // without the know how.
    $access = $this->currentUser->hasPermission('Administer menus and menu items');
    $form['label']['#access'] = $access;
    $form['description']['#access'] = $access;
    $form['id']['#access'] = $access;

    // Remove the warning message if the user does not have access.
    if (!$access) {
      $this->messenger->deleteByType('warning');
    }
  }

}
