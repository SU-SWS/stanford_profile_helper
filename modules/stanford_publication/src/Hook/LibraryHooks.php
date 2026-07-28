<?php

declare(strict_types=1);

namespace Drupal\stanford_publication\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\views\ViewExecutable;
use Symfony\Component\Finder\Finder;

/**
 * Hooks that build and attach the module's asset libraries.
 */
class LibraryHooks {

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   Module extension list service.
   */
  public function __construct(protected ModuleExtensionList $moduleExtensionList) {}

  /**
   * Implements hook_library_info_build().
   */
  #[Hook('library_info_build')]
  public function libraryInfoBuild(): array {
    $libraries = [];
    $module_path = $this->moduleExtensionList->getPath('stanford_publication');

    // Find all css files in the dist/css directory.
    $finder = new Finder();
    $finder->in("$module_path/dist/css")
      ->files()
      ->name('/.css$/');

    foreach ($finder->getIterator() as $file) {
      $local_path = str_replace("$module_path/", '', $file->getPath());

      $path_parts = explode('/', $local_path);
      // Remove `dist` and `css` parts.
      unset($path_parts[0], $path_parts[1]);

      // This is the directory the file lives in.
      $library_level = reset($path_parts);
      $bucket = next($path_parts);
      $lib = basename($file->getFilename(), '.css');

      // Build the library definition.
      $libraries[trim("$bucket.$lib", '. ')] = [
        'css' => [
          $library_level => [
            "$local_path/{$file->getFileName()}" => [],
          ],
        ],
      ];
    }
    return $libraries;
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_node')]
  public function preprocessNode(&$variables): void {
    if (!isset($variables['page']) || !$variables['page']) {
      return;
    }

    if (isset($variables['node']) && $variables['node'] instanceof NodeInterface && $variables['node']->bundle() == 'stanford_publication') {
      $variables['#attached']['library'][] = 'stanford_publication/node.stanford_publication';
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_menu__stanford_publication_topics')]
  public function preprocessMenuStanfordPublicationTopics(&$variables): void {
    $variables['#attached']['library'][] = 'stanford_publication/menu.taxonomy_menu';
    $variables['#attached']['library'][] = 'stanford_publication/taxonomy_menu';
  }

  /**
   * Implements hook_views_pre_render().
   */
  #[Hook('views_pre_render')]
  public function viewsPreRender(ViewExecutable $view): void {
    if ($view->id() == 'stanford_publications') {
      $view->element['#attached']['library'][] = 'stanford_publication/views.stanford_publication';
    }
  }

}
