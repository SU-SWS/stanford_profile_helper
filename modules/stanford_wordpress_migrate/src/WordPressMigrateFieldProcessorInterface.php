<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate;

use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Interface for wordpress_migrate_field_processor plugins.
 */
interface WordPressMigrateFieldProcessorInterface {

  /**
   * Returns the translated plugin label.
   */
  public function label(): string;

  /**
   * Get the list of columns that can be mapped for destination.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   Field storage entity.
   *
   * @return array
   *   Keyed array of column and labels.
   */
  public function getFieldColumns(FieldDefinitionInterface $field): array;

  /**
   * Get any constants that might be needed for the process.
   *
   * @return array
   *   Keyed array of constants.
   */
  public function getConstants(): array;

  /**
   * Get the plugin process configuration.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   Field storage entity.
   * @param string|null $column
   *   Destination field column, if the field has multiple columns.
   *
   * @return array
   *   Indexed array of default process plugin configuration.
   */
  public function getProcess(FieldDefinitionInterface $field, ?string $column = NULL): array;

  /**
   * Get any additional process plugins, maybe setting default column values.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   Field storage entity.
   *
   * @return array
   *   Any additional process plugin configuration.
   */
  public function getExtraProcess(FieldDefinitionInterface $field): array;

  /**
   * Get the plugin name that is used when multiple sources are mapped to the
   * same field.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   Field storage entity.
   *
   * @return string
   *   Possibly, null_coalesce, concat, get, etc.
   */
  public function getMultiplePlugin(FieldDefinitionInterface $field): string;

  /**
   * Set the WordPress migration content entity for the plugin.
   *
   * @param \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration
   *   Content entity object.
   *
   * @return self
   */
  public function setWordPressMigration(WordPressMigrationInterface $migration): self;

}
