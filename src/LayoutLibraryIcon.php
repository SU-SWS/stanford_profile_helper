<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\layout_library\Entity\Layout;

/**
 * Service to look up or create a file icon for the layout library.
 */
final class LayoutLibraryIcon implements LayoutLibraryIconInterface {

  /**
   * Constructs a LayoutLibraryIcon object.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly FileSystemInterface $fileSystem,
    private readonly FileUsageInterface $fileUsage,
    private readonly AccountProxyInterface $account,
    private readonly ModuleHandlerInterface $moduleHandler,
    private readonly UuidInterface $uuid
  ) {}

  /**
   * {@inheritdoc}
   */
  public function getLayoutIcon(Layout $layout): ?FileInterface {
    $icon = $layout->getThirdPartySetting('stanford_profile_helper', 'icon', []);
    if (isset($icon['uuid'])) {
      $files = $this->entityTypeManager->getStorage('file')
        ->loadByProperties(['uuid' => $icon['uuid']]);
      if ($files) {
        return reset($files);
      }
      return $this->createFile($layout->id(), $icon['uuid'], $icon['data']);
    }
    return $this->getDefaultIcon();
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultIcon(): ?FileInterface {
    $default_uri = self::IMAGE_DIRECTORY . 'default-default-icon.png';
    $files = $this->entityTypeManager->getStorage('file')
      ->loadByProperties(['uri' => $default_uri]);
    if ($files) {
      return reset($files);
    }

    $default_path = $this->moduleHandler->getModule('stanford_profile_helper')
        ->getPath() . '/icons/layout-library-default.png';
    $icon_data = 'data:image/png;base64,' . base64_encode(file_get_contents($default_path));

    return $this->createFile('default', $this->uuid->generate(), $icon_data);
  }

  /**
   * Create a new file using the encoded data string, return the file entity.
   *
   * @param string $id
   *   Layout ID use to create the file name.
   * @param string $uuid
   *   File entity UUID.
   * @param string $data
   *   Base 64 encoded image string.
   *
   * @return \Drupal\file\FileInterface|null
   *   Generated file entity or null if any failures.
   */
  protected function createFile(string $id, string $uuid, string $data): ?FileInterface {
    $icon_data = fopen($data, 'r');

    // Compose the default icon file destination.
    $icon_meta = stream_get_meta_data($icon_data);
    // File extension from MIME, only JPG/JPEG, PNG and SVG expected.
    [, $icon_file_ext] = explode('image/', $icon_meta['mediatype']);
    // SVG special case.
    if ($icon_file_ext == 'svg+xml') {
      $icon_file_ext = 'svg';
    }

    $icon_upload_path = self::IMAGE_DIRECTORY;
    $icon_file_destination = $icon_upload_path . $id . '-default-icon.' . $icon_file_ext;
    // Check the directory exists before writing data to it.
    $this->fileSystem->prepareDirectory($icon_upload_path, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    // Save the default icon file.
    $icon_file_uri = $this->fileSystem->saveData($icon_data, $icon_file_destination);
    if ($icon_file_uri) {
      // Create the icon file entity.
      $icon_entity_values = [
        'uri' => $icon_file_uri,
        'uid' => $this->account->id(),
        'uuid' => $uuid,
        'status' => FileInterface::STATUS_PERMANENT,
      ];

      /** @var \Drupal\file\FileInterface $new_icon */
      $new_icon = $this->entityTypeManager->getStorage('file')
        ->create($icon_entity_values);
      $new_icon->save();
      $this->fileUsage->add($new_icon, 'layout_library', 'layout', $id);

      return $new_icon;
    }
    return NULL;
  }

}
