<?php

declare(strict_types=1);

namespace Drupal\stanford_person_importer\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Help hooks for stanford_person_importer.
 */
class HelpHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      // Main module help for the stanford_person_importer module.
      case 'help.page.stanford_person_importer':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('Migration support for importing of profile information from stanford.edu.') . '</p>';
        return $output;

      default:
    }
  }

}
