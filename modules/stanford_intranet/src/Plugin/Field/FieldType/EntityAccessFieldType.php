<?php

namespace Drupal\stanford_intranet\Plugin\Field\FieldType;

use Drupal\Core\Field\Attribute\FieldType;
use Drupal\Core\Field\FieldItemBase;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\TypedData\DataDefinition;

/**
 * Plugin implementation of the 'entity_access' field type.
 */
#[FieldType(
  id: "entity_access",
  label: new TranslatableMarkup("Entity access field type"),
  default_widget: "language_select",
  cardinality: FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
  no_ui: TRUE
)]
class EntityAccessFieldType extends FieldItemBase {

  const FIELD_NAME = 'stanford_intranet__access';

  /**
   * {@inheritdoc}
   */
  public static function propertyDefinitions(FieldStorageDefinitionInterface $field_definition) {
    // Prevent early t() calls by using the TranslatableMarkup.
    $properties['role'] = DataDefinition::create('string')
      ->setLabel(new TranslatableMarkup('User Role'))
      ->setRequired(TRUE);

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $schema = [
      'columns' => [
        'role' => [
          'type' => 'varchar',
          'length' => 255,
        ],
        'access' => [
          'type' => 'blob',
          'size' => 'normal',
          'serialize' => TRUE,
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    $value = $this->get('role')->getValue();
    return $value === NULL || $value === '';
  }

}
