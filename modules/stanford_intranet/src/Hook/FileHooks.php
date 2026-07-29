<?php

declare(strict_types=1);

namespace Drupal\stanford_intranet\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\file\FileInterface;

/**
 * Hooks that control file downloads and access on the intranet.
 */
class FileHooks {

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $moduleHandler
   *   Core module handler service.
   */
  public function __construct(
    protected StateInterface $state,
    protected ModuleHandlerInterface $moduleHandler,
  ) {}

  /**
   * Implements hook_file_download().
   */
  #[Hook('file_download')]
  public function fileDownload($uri) {
    // When viewing images that were converted to PNG files, the Image Effects
    // module appends the `.png` extension onto the end of the existing uri.
    // When a user visits a page with an image that was converted to PNG, Drupal
    // core throws an access-denied error because it's unable to find the original
    // image because of the extension changing. We simply need to remove the
    // ending png extension and then pass it back to the core hook.
    // @see \Drupal\image\Controller\ImageStyleDownloadController::deliver().
    // @see file_file_download().
    if (preg_match('/.jp[e]?g.png$/', $uri) && StreamWrapperManager::getScheme($uri) == 'private') {
      return $this->moduleHandler
        ->invokeAll('file_download', [str_replace('.png', '', $uri)]);
    }

    $file_repository = \Drupal::service('file.repository');
    $file = $file_repository->loadByUri($uri);
    if (!$file) {
      return;
    }

    $usage_list = \Drupal::service('file.usage')->listUsage($file);
    // Allow icon files to be viewed. All other files on the system are referenced
    // via media entities, so they will go through normal access checks. This
    // allows media library icons, paragraph type icons, etc to be viewed and
    // downloaded.
    if (!isset($usage_list['file']['media'])) {
      return $file->getDownloadHeaders();
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('file_access')]
  public function fileAccess(FileInterface $file, $operation, AccountInterface $account) {
    $usage = \Drupal::service('file.usage')->listUsage($file);

    // Allow the user to "download" the file if it meets the conditions. This
    // allows images that are saved on config pages to be viewed by authenticated
    // users. Such fields like the logo field.
    $allowed = $this->state->get('stanford_intranet', FALSE) &&
      $account->isAuthenticated() &&
      isset($usage['file']['config_pages']) &&
      (in_array($operation, ['download', 'view']));
    return AccessResult::allowedIf($allowed);
  }

}
