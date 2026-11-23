<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper;

use Drupal\file\FileInterface;
use Drupal\layout_library\Entity\Layout;

/**
 * Layout library icon service.
 */
interface LayoutLibraryIconInterface {

  const IMAGE_DIRECTORY = 'public://layout-icon/';

  /**
   * Get the file entity for the icon configured on the layout.
   *
   * @param \Drupal\layout_library\Entity\Layout $layout
   *   Layout library entity.
   *
   * @return \Drupal\file\FileInterface|null
   *   File entity for the icon, null if none exists.
   */
  public function getLayoutIcon(Layout $layout): ?FileInterface;

  /**
   * Get the default icon file used when a layout has no icon of its own.
   *
   * @return \Drupal\file\FileInterface|null
   *   Default icon file entity, null if it doesn't exist.
   */
  public function getDefaultIcon(): ?FileInterface;

}
