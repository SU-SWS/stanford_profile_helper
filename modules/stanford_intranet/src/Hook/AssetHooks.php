<?php

declare(strict_types=1);

namespace Drupal\stanford_intranet\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\State\StateInterface;
use Symfony\Component\Finder\Finder;

/**
 * Hooks that manage front-end assets and libraries for the intranet.
 */
class AssetHooks {

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   * @param \Drupal\Core\Routing\AdminContext $adminContext
   *   Admin route context service.
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   Module extension list service.
   */
  public function __construct(
    protected StateInterface $state,
    protected AdminContext $adminContext,
    protected ModuleExtensionList $moduleExtensionList,
  ) {}

  /**
   * Implements hook_page_attachments().
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    if (
      $this->state->get('stanford_intranet', FALSE) &&
      !$this->adminContext->isAdminRoute()
    ) {
      $attachments['#attached']['library'][] = "stanford_intranet/intranet";
    }
  }

  /**
   * Implements hook_library_info_build().
   */
  #[Hook('library_info_build')]
  public function libraryInfoBuild(): array {
    $libraries = [];
    $module_path = $this->moduleExtensionList->getPath('stanford_intranet');

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

}
