<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Component\Utility\Html;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hooks that relate to general theming and preprocessing.
 */
class ThemeHooks {

  use StringTranslationTrait;

  /**
   * Theme hook constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Current route match service.
   */
  public function __construct(protected ModuleHandlerInterface $moduleHandler, protected RouteMatchInterface $routeMatch) {}

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_html')]
  public function preprocessHtml(&$variables): void {
    $environment = 'sws-other';

    if ($this->moduleHandler->moduleExists('acquia_purge')) {
      $environment = 'sws-acquia';
    }

    if ($this->moduleHandler->moduleExists('acsf')) {
      $environment = 'sws-acsf';
    }
    // Add a class to the body tag to identify sws applications.
    $variables['attributes']['class'][] = $environment;
  }

  /**
   * Implements hook_theme_registry_alter().
   */
  #[Hook('theme_registry_alter')]
  public function themeRegistryAlter(&$theme_registry): void {
    $theme_registry['cshs_term_group']['variables']['field_name'] = '';
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_field__entity_reference')]
  public function preprocessFieldEntityReference(&$variables): void {
    foreach ($variables['items'] as &$item) {
      // Add the field name so it can be used in the theme suggestions below.
      $item['content']['#field_name'] = $variables['field_name'];
    }
  }

  /**
   * Implements hook_theme_suggestions_HOOK().
   */
  #[Hook('theme_suggestions_cshs_term_group')]
  public function themeSuggestionsCshsTermGroup(array $variables): array {
    return ['cshs_term_group__' . $variables['field_name']];
  }

  /**
   * Implements hook_theme().
   */
  #[Hook('theme')]
  public function theme($existing, $type, $theme, $path): array {
    $themes['rabbit_hole_message'] = [
      'variables' => ['destination' => NULL],
    ];
    return $themes;
  }

  /**
   * Implements hook_library_info_alter().
   */
  #[Hook('library_info_alter')]
  public function libraryInfoAlter(&$libraries, $extension): void {
    if ($extension == 'views') {
      $libraries['views.ajax']['dependencies'][] = 'stanford_profile_helper/ajax_views';
    }

    if ($extension == 'mathjax') {
      $libraries['source']['dependencies'][] = 'stanford_profile_helper/mathjax';
      unset($libraries['setup'], $libraries['config']);
    }

    // Rely on the fontawesome module to provide the library.
    if (
      $extension == 'stanford_basic' &&
      $this->moduleHandler->moduleExists('fontawesome')
    ) {
      unset($libraries['fontawesome']);
    }

    foreach ($libraries as &$library) {
      if (isset($library['dependencies'])) {
        foreach ($library['dependencies'] as $key => $dependency) {
          if ($dependency == 'pdb_react/react') {
            unset($library['dependencies'][$key]);
          }
        }
      }
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_toolbar')]
  public function preprocessToolbar(&$variables): void {
    array_walk($variables['tabs'], function (&$tab, $key) {
      if (isset($tab['attributes'])) {
        $tab['attributes']->addClass(Html::cleanCssIdentifier("$key-tab"));
      }
    });
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_block__help')]
  public function preprocessBlockHelp(&$variables): void {
    if ($this->routeMatch->getRouteName() == 'help.main') {
      // Removes the help text from core help module. Its not helpful, and
      // we're going to provide our own help text.
      // @see help_help()
      unset($variables['content']);
    }
  }

  /**
   * Implements hook_help_section_info_alter().
   */
  #[Hook('help_section_info_alter')]
  public function helpSectionInfoAlter(array &$info): void {
    // Change "Module overviews" header.
    $info['hook_help']['title'] = $this->t('For Developers');
  }

}
