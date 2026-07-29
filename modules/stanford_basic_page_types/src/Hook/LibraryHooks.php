<?php

declare(strict_types=1);

namespace Drupal\stanford_basic_page_types\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Symfony\Component\Finder\Finder;

/**
 * Hooks that build and attach libraries for stanford basic page types.
 */
class LibraryHooks {

  /**
   * Library hooks constructor.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Current route match service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   Module extension list service.
   */
  public function __construct(protected RouteMatchInterface $routeMatch, protected ModuleExtensionList $moduleExtensionList) {}

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    // Get the node from the route.
    $node = $this->routeMatch->getParameter('node');

    // Not a node.. Then just continue.
    if (!$node instanceof NodeInterface) {
      return;
    }

    $node_type = $node->getType();
    // Check for our type and add library if a match.
    if ($node_type == "stanford_page") {
      $attachments['#attached']['library'][] = "stanford_basic_page_types/node.stanford-page";
    }

  }

  /**
   * Implements hook_library_info_build().
   */
  #[Hook('library_info_build')]
  public function libraryInfoBuild(): array {
    $libraries = [];
    $module_path = $this->moduleExtensionList->getPath('stanford_basic_page_types');

    // Find all css files in the dist/css directory.
    $finder = new Finder();
    $finder->files()
      ->in("$module_path/dist/css")
      ->name('/.css$/');

    foreach ($finder as $file) {

      $local_path = str_replace("$module_path/", '', $file->getPath());
      $path_parts = explode('/', $local_path);
      // Remove `dist` and `css` parts.
      unset($path_parts[0], $path_parts[1]);

      // This is the directory the file lives in.
      $library_level = reset($path_parts);
      $bucket = 'node';
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

}
