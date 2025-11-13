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
  id: 'link',
  label: new TranslatableMarkup('Link'),
  fieldType: ['link']
)]
class LinkField extends WordPressMigrateFieldProcessorPluginBase {

  /**
   * {@inheritDoc}
   */
  public function getProcess(FieldDefinitionInterface $field, ?string $column = NULL): array {
    $process = parent::getProcess($field, $column);
    if ($column == 'uri') {
      // Make sure to prepend links with http:// if they started with www.
      $process[] = [
        'plugin' => 'str_replace',
        'regex' => TRUE,
        'search' => '/^www/',
        'replace' => 'http://www',
      ];
      // Check the url to avoid greater errors elsewhere.
      $process[] = [
        'plugin' => 'url_check',
        'method' => 'process',
      ];
    }
    if ($column == 'title') {
      $process[] = [
        'plugin' => 'callback',
        'callable' => 'html_entity_decode',
      ];
      $process[] = [
        'plugin' => 'substr',
        'start' => 0,
        'length' => 255,
      ];
    }
    return $process;
  }

}
