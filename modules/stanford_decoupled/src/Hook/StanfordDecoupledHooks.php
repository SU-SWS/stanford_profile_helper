<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\Field\FieldConfigInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeTypeInterface;
use Drupal\stanford_decoupled\Plugin\Next\Revalidator\Path;

/**
 * Hooks to help decoupled functionality.
 */
class StanfordDecoupledHooks {

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory) {}

  /**
   * @param $plugins
   */
  #[Hook('next_revalidator_info_alter')]
  public function nextRevalidatorInfoAlter(&$plugins) {
    $plugins['path']['class'] = Path::class;
  }

  /**
   * Set graphql settings when a node bundle is created.
   *
   * @param \Drupal\node\NodeTypeInterface $nodeType
   *   Created node type.
   */
  #[Hook('node_type_insert')]
  public function onNodeTypeCreate(NodeTypeInterface $nodeType): void {
    $configKey = sprintf('entity_config.node.%s', $nodeType->id());
    $this->setGraphqlConfig($configKey, [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
      'edges_enabled' => TRUE,
      'routes_enabled' => TRUE,
    ]);
  }

  /**
   * When a new bundle for certain entity types is created, add it to GraphQL.
   *
   * @param \Drupal\Core\Config\Entity\ConfigEntityInterface $entity
   *   New entity type or entity bundle.
   */
  #[Hook('taxonomy_vocabulary_insert')]
  #[Hook('paragraphs_type_insert')]
  #[Hook('config_pages_type_insert')]
  #[Hook('media_type_insert')]
  public function onEntityBundleCreate(ConfigEntityInterface $entity): void {
    $entityType = $entity->getEntityType()->getBundleOf();
    $bundle = $entityType ? $entity->id() : NULL;
    $configKey = trim(sprintf('entity_config.%s.%s', $entityType, $bundle), ',');
    $this->setGraphqlConfig($configKey, [
      'enabled' => TRUE,
      'query_load_enabled' => TRUE,
    ]);
  }

  /**
   * Update graphql settings when a field is added to an entity.
   *
   * @param \Drupal\Core\Field\FieldConfigInterface $field
   *   New field config.
   */
  #[Hook('field_config_insert')]
  public function onFieldConfigCreate(FieldConfigInterface $field): void {
    $configKey = str_replace('..', '.', sprintf('field_config.%s.%s.%s.enabled', $field->getTargetEntityTypeId(), $field->getTargetBundle(), $field->getName()));
    $this->setGraphqlConfig($configKey, TRUE);
  }

  /**
   * Update graphql settings.
   *
   * @param string $key
   *   Path of config value to change.
   * @param mixed $value
   *   New value of config.
   */
  protected function setGraphqlConfig(string $key, $value): void {
    $graphQlConfig = $this->configFactory->getEditable('graphql_compose.settings');
    $graphQlConfig->set($key, $value);
    $graphQlConfig->save();
  }

}
