<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeInterface;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Hooks that alter GraphQL Compose output.
 */
class GraphqlComposeHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_graphql_compose_field_results_alter().
   */
  #[Hook('graphql_compose_field_results_alter')]
  public function fieldResultsAlter(array &$results, $entity, GraphQLComposeFieldTypeInterface $plugin, FieldContext $context) {
    $field_definition = $plugin->getFieldDefinition();
    if ($field_definition->getName() == 'layout_selection') {
      foreach ($results as &$result) {
        $result = ['id' => $result->id(), 'label' => $result->label()];
      }
    }

    foreach ($results as $item) {
      if ($item instanceof ParagraphInterface) {
        $behaviors = $item->getAllBehaviorSettings();
        $item->set('behavior_settings', $behaviors ? json_encode($behaviors) : NULL);
      }
    }
  }

  /**
   * Implements hook_graphql_compose_entity_base_fields_alter().
   */
  #[Hook('graphql_compose_entity_base_fields_alter')]
  public function entityBaseFieldsAlter(array &$fields, string $entity_type_id) {
    if ($entity_type_id == 'paragraph') {
      $fields['behavior_settings'] = [
        'field_type' => 'string',
        'name_sdl' => 'behaviors',
        'required' => FALSE,
        'description' => $this->t('Paragraph Behavior Settings.'),
      ];
    }
  }

}
