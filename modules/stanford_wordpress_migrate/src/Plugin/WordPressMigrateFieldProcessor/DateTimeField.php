<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\stanford_wordpress_migrate\Attribute\WordPressMigrateFieldProcessor;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginBase;

/**
 * Plugin implementation of the wordpress_migrate_field_processor.
 */
#[WordPressMigrateFieldProcessor(
  id: 'datetime',
  label: new TranslatableMarkup('Date Time'),
  fieldType: ['datetime', 'daterange']
)]
class DateTimeField extends WordPressMigrateFieldProcessorPluginBase {

  /**
   * {@inheritDoc}
   */
  public function getProcess(FieldDefinitionInterface $field, ?string $column = NULL): array {
    $process = parent::getProcess($field, $column);
    $type = $field->getFieldStorageDefinition()->getSetting('datetime_type');

    $format = $type == DateTimeItem::DATETIME_TYPE_DATE ? DateTimeItemInterface::DATE_STORAGE_FORMAT : DateTimeItemInterface::DATETIME_STORAGE_FORMAT;
    // Convert data to timestamp, then to the datetime field format.
    $process[] = [
      'plugin' => 'callback',
      'callable' => 'strtotime',
    ];
    // Skip if the strtotime failed.
    $process[] = [
      'plugin' => 'skip_on_empty',
      'method' => 'process',
    ];
    $process[] = [
      'plugin' => 'format_date',
      'from_format' => 'U',
      'to_format' => $format,
    ];
    return $process;
  }

}
