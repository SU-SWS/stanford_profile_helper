<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\Entity;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Entity\Attribute\ContentEntityType;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\stanford_wordpress_migrate\Wizard\ImportAddWizard;
use Drupal\stanford_wordpress_migrate\Wizard\ImportEditWizard;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrationListBuilder;
use Drupal\views\EntityViewsData;

/**
 * Defines the WordPress migration entity class.
 */
#[ContentEntityType(
  id: 'wordpress_migration',
  label: new TranslatableMarkup('WordPress Migration'),
  label_collection: new TranslatableMarkup('WordPress Migrations'),
  label_singular: new TranslatableMarkup('wordpress migration'),
  label_plural: new TranslatableMarkup('wordpress migrations'),
  entity_keys: [
    'id' => 'id',
    'label' => 'label',
    'published' => 'status',
    'uuid' => 'uuid',
  ],
  handlers: [
    'list_builder' => WordPressMigrationListBuilder::class,
    'views_data' => EntityViewsData::class,
    'form' => [
      'delete' => ContentEntityDeleteForm::class,
    ],
    'wizard' => [
      'add' => ImportAddWizard::class,
      'edit' => ImportEditWizard::class,
    ],
  ],
  links: [
    'collection' => '/admin/structure/migrate/wordpress',
    'add-form' => '/admin/structure/migrate/wordpress/add',
    'canonical' => '/admin/structure/migrate/wordpress/{wordpress_migration}',
    'edit-form' => '/admin/structure/migrate/wordpress/manage/{machine_name}/{step}',
    'delete-form' => '/admin/structure/migrate/wordpress/{wordpress_migration}/delete',
    'enable' => '/admin/structure/migrate/wordpress/{wordpress_migration}/enable',
    'disable' => '/admin/structure/migrate/wordpress/{wordpress_migration}/disable',
  ],
  admin_permission: 'administer wordpress_migration',
  base_table: 'wordpress_migration',
  label_count: [
    'singular' => '@count WordPress migration',
    'plural' => '@count WordPress migrations',
  ],
)]
class WordPressMigration extends ContentEntityBase implements WordPressMigrationInterface {

  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
    $fields = parent::baseFieldDefinitions($entity_type);
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['base_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Base URL'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255);

    $fields['configuration'] = BaseFieldDefinition::create('map')
      ->setLabel(t('Configuration'))
      ->setDefaultValue([]);

    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function enable(): self {
    $this->set('status', TRUE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function disable(): self {
    $this->set('status', FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    parent::postSave($storage, $update);
    \Drupal::service('plugin.manager.migration')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage, array $entities) {
    parent::postDelete($storage, $entities);
    \Drupal::service('plugin.manager.migration')->clearCachedDefinitions();
  }

  /**
   * {@inheritdoc}
   */
  public function getBaseUrl(): ?string {
    return $this->get('base_url')?->getString();
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->get('configuration')->get(0)?->getValue() ?: [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfigurationValue(string|array $key, $value): void {
    $config = $this->getConfiguration();
    $key = is_string($key) ? [$key] : $key;
    NestedArray::setValue($config, $key, $value);
    $this->set('configuration', $config);
  }

  /**
   * {@inheritdoc}
   */
  public function getConfigurationValue(string|array $key, $default_value = NULL) {
    $config = $this->getConfiguration();
    $key = is_string($key) ? [$key] : $key;
    return NestedArray::getValue($config, $key) ?: $default_value;
  }

}
