<?php

namespace Drupal\stanford_decoupled\Plugin\Next\PreviewUrlGenerator;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;
use Drupal\next\Entity\NextSiteInterface;
use Drupal\next\Plugin\PreviewUrlGeneratorBase;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides the preview_url_generator plugin based on simple query string.
 *
 * @PreviewUrlGenerator(
 *  id = "simple_preview",
 *  label = "Simple Preview",
 *  description = "Use the preview token string for the parameter."
 * )
 */
class SimplePreview extends PreviewUrlGeneratorBase {

  /**
   * {@inheritdoc}
   */
  public function generate(NextSiteInterface $next_site, EntityInterface $entity, string $resource_version = NULL): ?Url {
    return Url::fromUri($next_site->getPreviewUrl(), [
      'query' => [
        'slug' => $entity->toUrl()->toString(TRUE)->getGeneratedUrl(),
        'secret' => $next_site->getPreviewSecret(),
      ],
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function validate(Request $request) {}

}
