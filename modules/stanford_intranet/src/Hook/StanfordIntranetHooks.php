<?php

declare(strict_types=1);

namespace Drupal\stanford_intranet\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\stanford_intranet\Plugin\Field\FieldType\EntityAccessFieldType;

/**
 * Hooks to modify behaviors for an intranet.
 */
class StanfordIntranetHooks {

  /**
   * Implements hook_ENTITY_update().
   *
   * Make sure to clear the algolia record if a node has restricted access.
   */
  #[Hook('node_update')]
  public function nodeUpdate(NodeInterface $node) {
    if (
      $node->hasField(EntityAccessFieldType::FIELD_NAME) &&
      $node->get(EntityAccessFieldType::FIELD_NAME)->count()
    ) {
      self::clearAlgolia($node);
    }
  }

  /**
   * Clear the algolia index item immediately.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to delete.
   *
   * @codeCoverageIgnore
   */
  protected static function clearAlgolia(NodeInterface $node) {
    if (\Drupal::hasService('search_api_algolia.helper')) {
      \Drupal::service('search_api_algolia.helper')->entityDelete($node);
    }
  }

}
