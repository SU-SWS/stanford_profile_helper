<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\stanford_wordpress_migrate\Attribute\WordPressMigrateFieldProcessor;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginBase;

/**
 * Plugin implementation of the wordpress_migrate_field_processor.
 */
#[WordPressMigrateFieldProcessor(
  id: 'string',
  label: new TranslatableMarkup('String'),
  fieldType: [
    'string',
    'string_long',
    'email',
    'list_string',
    'telephone',
    'name',
  ]
)]
class StringField extends WordPressMigrateFieldProcessorPluginBase {

  /**
   * {@inheritDoc}
   */
  public function getProcess(FieldDefinitionInterface $field, ?string $column = NULL): array {
    $process = parent::getProcess($field, $column);
    $fieldStorage = $field->getFieldStorageDefinition();

    $process[] = [
      'plugin' => 'callback',
      'callable' => 'html_entity_decode',
    ];
    $process[] = [
      'plugin' => 'callback',
      'callable' => 'strip_tags',
    ];
    $maxLength = $field->getType() == 'string_long' ? 0 : ($fieldStorage->getSetting('max_length') ?: 255);

    // Trim the field to a maximum length to prevent SQL issues.
    if ($maxLength) {
      $process[] = [
        'plugin' => 'substr',
        'start' => 0,
        'length' => $maxLength,
      ];
    }
    return $process;
  }

}
