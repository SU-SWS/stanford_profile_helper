<?php

declare(strict_types=1);

namespace Drupal\stanford_image_styles\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\image\ImageStyleInterface;

/**
 * Hooks that modify image style configuration.
 */
class StanfordImageStylesHooks {

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\Extension\ModuleExtensionList $moduleExtensionList
   *   Module extension list service.
   */
  public function __construct(protected ModuleExtensionList $moduleExtensionList) {}

  /**
   * Implements hook_ENTITY_TYPE_presave() for image_style entities.
   */
  #[Hook('image_style_presave')]
  public function imageStylePresave(ImageStyleInterface $image_style): void {
    // Set the path for the mask image for the circle image style when the image
    // style is created new.
    if ($image_style->id() == 'stanford_circle' && $image_style->isNew()) {
      $effects = $image_style->get('effects');
      foreach ($effects as &$effect) {
        if ($effect['id'] == 'image_effects_mask') {
          $module_path = $this->moduleExtensionList->getPath('stanford_image_styles');
          $effect['data']['mask_image'] = "$module_path/img/mask-image.png";
        }
      }
      $image_style->set('effects', $effects);
    }
  }

}
