<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\Hook;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Token hooks for content entities.
 */
class TokenHooks {

  use StringTranslationTrait;

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Implements hook_token_info().
   */
  #[Hook('token_info')]
  public function tokenInfo() {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $info = [];

    foreach ($entity_types as $entity_id => $entity_type) {
      if ($entity_type->getGroup() == 'content') {
        $info['tokens'][$entity_id]['uuid'] = [
          'name' => $this->t('@entity_id UUID', ['@entity_id' => $entity_type->getLabel()]),
          'description' => $this->t('The Universal Unique Identifier of @entity_id', ['@entity_id' => $entity_type->getLabel()]),
        ];
      }
    }
    return $info;
  }

  /**
   * Implements hook_tokens().
   */
  #[Hook('tokens')]
  public function tokens($type, $tokens, array $data = [], array $options = []) {
    $replacements = [];
    if (!empty($data[$type]) && $data[$type] instanceof ContentEntityInterface) {
      $entity = $data[$type];

      foreach ($tokens as $name => $original) {
        switch ($name) {
          case 'uuid':
            $replacements[$original] = $entity->uuid();
            break;
        }
      }
    }
    return $replacements;
  }

}
