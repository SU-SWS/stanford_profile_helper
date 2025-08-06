<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\config_pages\ConfigPagesInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Installer\InstallerKernel;
use Drupal\Core\State\StateInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;

/**
 * Hooks that modify the functionality of config pages.
 */
class ConfigPagesHooks {

  public function __construct(protected StateInterface $state) {}

  /**
   * Before saving the config page, set the renewal date a year out.
   *
   * @codeCoverageIgnore
   *
   * @param \Drupal\config_pages\ConfigPagesInterface $configPage
   *   Config page entity.
   */
  #[Hook('config_pages_presave')]
  public function configPagesPreSaveUpdateRenewal(ConfigPagesInterface $configPage) {
    if (
      PHP_SAPI != 'cli' &&
      $configPage->bundle() == 'stanford_basic_site_settings' &&
      self::redirectUser()
    ) {
      $renewal_date = time() + 60 * 60 * 24 * 365;
      $configPage->set('su_site_renewal_due', date(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $renewal_date));
      Cache::invalidateTags(['site-renew-date']);
    }
  }


  /**
   * Before saving a configuration page, set some state and clear caches.
   *
   * @param \Drupal\config_pages\ConfigPagesInterface $configPage
   *   The configuration page being saved.
   */
  #[Hook('config_pages_presave')]
  public function configPagesPreSave(ConfigPagesInterface $configPage) {
    if (InstallerKernel::installationAttempted()) {
      // Rebuild the routes so that the config pages will save from the default
      // content import at site installation.
      \Drupal::service('router.builder')->rebuildIfNeeded();
    }

    $state = \Drupal::state();
    if ($configPage->hasField('su_site_nobots')) {
      $enable_nobots = (bool) $configPage->get('su_site_nobots')->getString();
      $enable_nobots ? $this->state->set('nobots', TRUE) : $this->state->delete('nobots');
    }

    if (
      $configPage->hasField('su_site_url') &&
      $configPage->get('su_site_url')->count()
    ) {
      // Set the xml sitemap module state to the new domain.
      $state->set('xmlsitemap_base_url', $configPage->get('su_site_url')
        ->get(0)
        ->get('uri')
        ->getString());
    }

    // Invalidate cache tags on config pages save. This is a blanket cache clear
    // since config pages mostly affect the entire site.
    Cache::invalidateTags([
      'config:system.site',
      'system.site',
      'block_view',
      'node_view',
    ]);
  }

  /**
   * Check if the current user should be redirected to the site settings form.
   *
   * @return bool
   *   Redirect the user.
   */
  public static function redirectUser() {
    $current_user = \Drupal::currentUser();
    $cache = \Drupal::cache();

    /** @var \Drupal\Core\Routing\CurrentRouteMatch $route_match */
    $route_match = \Drupal::service('current_route_match');
    $name = $route_match->getCurrentRouteMatch()->getRouteName();
    $ignore_routes = [
      'system.css_asset',
      'system.js_asset',
      'image.style_private',
      'system.files',
      'system.private_file_download',
    ];
    if (in_array($name, $ignore_routes)) {
      return FALSE;
    }

    $cache_data = $cache->get('su_renew_site:' . $current_user->id());
    if ($cache_data) {
      return $cache_data->data;
    }

    /** @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface $config_page_loader */
    $config_page_loader = \Drupal::service('config_pages.loader');
    $renewal_date = $config_page_loader->getValue('stanford_basic_site_settings', 'su_site_renewal_due', 0, 'value') ?: date(DateTimeItemInterface::DATETIME_STORAGE_FORMAT);

    // Check for config page edit access and ignore if the user is an
    // administrator. That way devs don't get forced into submitting the form.
    $site_manager = $current_user->hasPermission('edit stanford_basic_site_settings config page entity') && !in_array('administrator', $current_user->getRoles());

    // If the renewal date has passed, they should be redirected.
    $needs_renewal = !getenv('CI') && $site_manager && strtotime($renewal_date) < time();
    $cache->set('su_renew_site:' . $current_user->id(), $needs_renewal, strtotime($renewal_date), ['site-renew-date']);

    return $needs_renewal;
  }

}
