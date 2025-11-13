<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\media\MediaInterface;

/**
 * Returns responses for Stanford Profile Helper routes.
 */
final class MediaDialogController extends ControllerBase {

  /**
   * Page title callback.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Media entity.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   Page title from the media.
   */
  public function title(MediaInterface $media) {
    return $media->label();
  }

  /**
   * Just return the media view display.
   *
   * @param \Drupal\media\MediaInterface $media
   *    Media entity.
   *
   * @return array
   *   Media render array.
   */
  public function mediaDialog(MediaInterface $media): array {
    $build = $this->entityTypeManager()->getViewBuilder('media')->view($media);

    $build['#attached']['html_head'][] = [
      [
        '#tag' => 'meta',
        '#attributes' => [
          'name' => 'robots',
          'content' => 'noindex, nofollow',
        ],
      ],
      'stanford_profile_helper',
    ];

    return $build;
  }

}
