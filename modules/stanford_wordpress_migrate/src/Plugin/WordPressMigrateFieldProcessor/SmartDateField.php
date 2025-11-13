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
  id: 'smartdate',
  label: new TranslatableMarkup('Smart Date'),
  fieldType: ['smartdate']
)]
class SmartDateField extends WordPressMigrateFieldProcessorPluginBase {

  /**
   * {@inheritDoc}
   */
  public function getProcess(FieldDefinitionInterface $field, ?string $column = NULL): array {
    $process = parent::getProcess($field, $column);
    if ($column == 'value' || $column == 'end_value') {
      $process[] = [
        'plugin' => 'callback',
        'callable' => 'strtotime',
      ];
      $process[] = [
        'plugin' => 'skip_on_empty',
        'method' => 'process',
      ];
    }
    return $process;
  }

}
