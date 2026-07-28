<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_styles\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks that adjust the External Links module's icon/class behavior.
 */
class ExtlinkHooks {

  /**
   * Implements hook_extlink_settings_alter().
   *
   * Variant setting for the External Links.
   */
  #[Hook('extlink_settings_alter')]
  public function extlinkSettingsAlter(array &$settings): void {
    if ($this->hideExtLinkIcons()) {
      $settings['extlink_class'] = '';
    }
  }

  /**
   * Implements hook_page_attachments_alter().
   */
  #[Hook('page_attachments_alter')]
  public function pageAttachmentsAlter(array &$attachments): void {
    if (isset($attachments['#attached']['drupalSettings']['data']['extlink'])) {
      if ($this->hideExtLinkIcons()) {
        $attachments['#attached']['drupalSettings']['data']['extlink']['extAdditionalLinkClasses'] = '';
      }
    }
  }

  /**
   * Whether external link icons should be hidden per site settings.
   *
   * The config_pages module is not a hard dependency of this module, so the
   * service is looked up lazily rather than constructor injected.
   *
   * @return bool
   *   TRUE if the external link icons should be hidden.
   */
  protected function hideExtLinkIcons(): bool {
    return (bool) \Drupal::service('config_pages.loader')
      ->getValue('stanford_basic_site_settings', 'su_hide_ext_link_icons', 0, 'value');
  }

}
