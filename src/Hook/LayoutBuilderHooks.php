<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hooks that relate to the Layout Builder "Add Block" UI.
 */
class LayoutBuilderHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_plugin_filter_TYPE__CONSUMER_alter().
   *
   * Curate the blocks available in the Layout Builder "Add Block" UI.
   */
  #[Hook('plugin_filter_block__layout_builder_alter')]
  public function pluginFilterBlockLayoutBuilderAlter(array &$definitions, array $extra): void {
    foreach ($definitions as &$definition) {
      if ($definition['provider'] == 'menu_block') {
        // Change the category for blocks provided by the menu block module so
        // it is separate from the "system" menus.
        $definition['category'] = $this->t('Menu Block');
      }
    }
  }

}
