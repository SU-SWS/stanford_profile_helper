<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hooks that relate to the field_group module.
 */
class FieldGroupHooks {

  /**
   * Implements hook_field_group_form_process_build_alter().
   */
  #[Hook('field_group_form_process_build_alter')]
  public function fieldGroupFormProcessBuildAlter(&$element): void {
    // Hide / Show the field groups based on the enabled checkbox.
    if (isset($element['group_lockup_options'])) {
      $element['group_lockup_options']['#states'] = [
        'visible' => [
          ':input[name="su_lockup_enabled[value]"]' => [
            'checked' => FALSE,
          ],
        ],
      ];
      $element['group_logo_image']['#states'] = [
        'visible' => [
          ':input[name="su_lockup_enabled[value]"]' => [
            'checked' => FALSE,
          ],
        ],
      ];
    }
  }

}
