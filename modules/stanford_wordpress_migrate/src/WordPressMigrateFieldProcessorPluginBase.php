<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate;

use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Base class for wordpress_migrate_field_processor plugins.
 */
abstract class WordPressMigrateFieldProcessorPluginBase extends PluginBase implements WordPressMigrateFieldProcessorInterface {

  /**
   * WordPress migration content entity.
   *
   * @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface|null
   */
  protected ?WordPressMigrationInterface $migration;

  /**
   * Plugin constructor.
   *
   * @param array $configuration
   *   Keyed array of configuration.
   * @param $plugin_id
   *   Plugin id.
   * @param $plugin_definition
   *   Plugin definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    if (isset($configuration['migration'])) {
      $this->migration = $configuration['migration'];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function label(): string {
    // Cast the label to a string since it is a TranslatableMarkup object.
    return (string) $this->pluginDefinition['label'];
  }

  /**
   * {@inheritDoc}
   */
  public function getFieldColumns(FieldDefinitionInterface $field): array {
    $columns = [];
    $fieldColumns = $field->getFieldStorageDefinition()->getColumns();
    if (count($fieldColumns) == 1) {
      return [$field->getName() => $field->getLabel()];
    }

    foreach (array_keys($fieldColumns) as $column) {
      $columns[sprintf('%s/%s', $field->getName(), $column)] = sprintf('%s: %s', $field->getLabel(), $column);
    }
    return $columns;
  }

  /**
   * {@inheritDoc}
   */
  public function getConstants(): array {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getProcess(FieldDefinitionInterface $field, ?string $column = NULL): array {
    $skip = ['plugin' => 'skip_on_empty', 'method' => 'process'];
    $skip = array_filter($skip);
    return [$skip];
  }

  /**
   * {@inheritDoc}
   */
  public function getExtraProcess(FieldDefinitionInterface $field): array {
    return [];
  }

  /**
   * {@inheritDoc}
   */
  public function getMultiplePlugin(FieldDefinitionInterface $field): string {
    if ($field->getFieldStorageDefinition()->getCardinality() != 1) {
      return 'get';
    }
    return 'null_coalesce';
  }

  /**
   * {@inheritDoc}
   */
  public function setWordPressMigration(WordPressMigrationInterface $migration): self {
    $this->migration = $migration;
    return $this;
  }

}
