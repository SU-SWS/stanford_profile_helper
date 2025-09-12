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
  id: 'entity_reference',
  label: new TranslatableMarkup('Entity Reference'),
  fieldType: ['entity_reference']
)]
class EntityReferenceField extends WordPressMigrateFieldProcessorPluginBase {
  /**
   * {@inheritDoc}
   */
  public function getProcess(FieldDefinitionInterface $field, ?string $column = null): array {
    $termReference = $field->getSetting('target_type') == 'taxonomy_term';
    $mediaReference = $field->getSetting('target_type') == 'media';

    // Currently only taxonomy and media references are supported.
    if (!$termReference && !$mediaReference) {
      return [];
    }

    $possibleMigrations = $this->getPossibleTermMigrations($field) ?: ['wordpress_media:media'];
    $process = parent::getProcess($field, $column);
    $process[] = [
      'plugin' => 'migration_lookup',
      'migration' => $possibleMigrations,
      'stub_id' => end($possibleMigrations),
    ];
    return $process;
  }

  /**
   * Get a list of taxonomy migration ids for migration_lookup process plugin.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $destField
   *   Destination field for the process.
   *
   * @return array
   *   Indexed array of plugin ids.
   */
  protected function getPossibleTermMigrations(FieldDefinitionInterface $destField): array {
    $ids = [];
    $settings = $destField->getSetting('handler_settings');
    $target_bundles = $settings['target_bundles'] ?: [];

    foreach ($this->migration->getConfigurationValue('taxonomy_term', []) as $source => $destinations) {
      foreach (array_keys($destinations) as $destination) {
        if (!in_array($destination, $target_bundles)) {
          continue;
        }

        $id = 'wordpress_terms:' . basename($source) . "__$destination";
        if (isset($ids[$id])) {
          $id .= '-' . $this->migration->id();
        }
        $ids[] = $id;
      }
    }
    return $ids;
  }

}
