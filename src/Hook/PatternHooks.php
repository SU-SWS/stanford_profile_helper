<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Serialization\Yaml;

/**
 * Hooks that relate to Pattern Design Bank (PDB) patterns.
 */
class PatternHooks {

  /**
   * Pattern hook constructor.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Module handler service.
   * @param \Drupal\Core\Extension\ThemeHandlerInterface $themeHandler
   *   Theme handler service.
   */
  public function __construct(protected ModuleHandlerInterface $moduleHandler, protected ThemeHandlerInterface $themeHandler) {}

  /**
   * Implements hook_component_info_alter().
   */
  #[Hook('component_info_alter')]
  public function componentInfoAlter(&$components): void {
    foreach ($components as $id => $component) {
      // Check if the provider of the PDB component is enabled.
      // if (!$this->extensionEnabled($component)) {
      //   unset($components[$id]);
      // }
    }
  }

  /**
   * Traverse the PDB extension to see if it's module/theme/profile is enabled.
   *
   * @param \Drupal\Core\Extension\Extension $extension
   *   Discovered PDB extension object.
   *
   * @return bool
   *   If the PDB extension's provider is enabled.
   */
  protected function extensionEnabled(Extension $extension): bool {
    $path = $extension->getPath();

    // Traverse down the path of the extension to find a module/theme/profile
    // that can be checked for existance.
    while ($path) {
      // An info.yml file exists in the current path, check if it's enabled
      // as a theme, profile, or module.
      if ($info_files = glob("$path/*.info.yml")) {
        $info_file_path = $info_files[0];
        $name = basename($info_file_path, '.info.yml');
        $info_file = Yaml::decode(file_get_contents($info_file_path));

        if (isset($info_file['type'])) {
          switch ($info_file['type']) {
            case 'theme':
              return $this->themeHandler->themeExists($name);

            case 'module':
            case 'profile':
              return $this->moduleHandler->moduleExists($name);
          }
        }
      }

      // Pop off the last part of the path to go one level higher.
      $path = explode('/', $path);
      array_pop($path);
      $path = implode('/', $path);
    }
    return FALSE;
  }

}
