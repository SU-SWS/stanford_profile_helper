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
  id: 'text',
  label: new TranslatableMarkup('Long Text'),
  fieldType: ['text', 'text_long', 'text_with_summary']
)]
class TextField extends WordPressMigrateFieldProcessorPluginBase {

  /**
   * {@inheritDoc}
   */
  public function getProcess(FieldDefinitionInterface $field, ?string $column = NULL): array {
    $process = parent::getProcess($field, $column);
    $process[] = [
      'plugin' => 'callback',
      'callable' => 'html_entity_decode',
    ];
    $process[] = [
      'plugin' => 'media_wysiwyg_parse',
      'image_domain' => $this->migration->getBaseUrl(),
    ];
    return $process;
  }

  /**
   * {@inheritDoc}
   */
  public function getExtraProcess(FieldDefinitionInterface $field): array {
    $format = 'stanford_html';
    if ($allowed_formats = $field->getSetting('allowed_formats')) {
      $format = reset($allowed_formats);
    }
    return [
      $field->getName() . '/format' => [
        'plugin' => 'default_value',
        'default_value' => $format,
      ],
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function getMultiplePlugin(FieldDefinitionInterface $field): string {
    if ($field->getFieldStorageDefinition()->getCardinality() != 1) {
      return 'get';
    }
    return 'concat';
  }

}
