<?php

namespace Drupal\stanford_decoupled\Plugin\Next\Revalidator;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\next\Event\EntityActionEvent;
use Drupal\next\Plugin\Next\Revalidator\Path as NextPath;

/**
 * Overridden next module path plugin to support tokens in the additional paths.
 *
 * @codeCoverageIgnore
 */
class Path extends NextPath {

  /**
   * {@inheritDoc}
   */
  public function revalidate(EntityActionEvent $event): bool {
    $this->configuration['additional_paths'] = self::adjustAdditionalPaths($this->configuration['additional_paths'], $event->getEntity());
    return parent::revalidate($event);
  }

  /**
   * Convert tokens in the additional paths.
   *
   * @param string $paths
   *   Additional paths config.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity being revalidated.
   *
   * @return string
   *   Adjusted paths.
   */
  protected static function adjustAdditionalPaths(string $paths, ContentEntityInterface $entity) {
    return \Drupal::token()
      ->replace($paths, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
  }

}
