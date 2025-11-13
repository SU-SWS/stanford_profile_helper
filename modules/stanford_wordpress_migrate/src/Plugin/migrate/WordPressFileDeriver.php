<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\Plugin\migrate;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginManager;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * WordPress Content importer deriver.
 */
class WordPressFileDeriver extends WordPressMigrationDeriverBase {

  const MIGRATE_TABLE_NAME = 'migrate_map_wordpress_files__files';

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('http_client'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.wordpress_migrate_field_processor'),
      $container->get('database'),
    );
  }

  /**
   * Content migration plugin deriver constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   Guzzler client service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager service.
   * @param \Drupal\Core\Database\Connection $database
   *   Database service.
   */
  public function __construct(
    protected ClientInterface $client,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected WordPressMigrateFieldProcessorPluginManager $fieldProcessorPluginManager,
    protected Connection $database,
  ) {}

  /**
   * {@inheritDoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $migrations = $this->getWordPressMigrations();
    if (!$migrations) {
      return $this->derivatives;
    }

    $mediaIds = $this->getMediaIds();
    $sourceUrls = [];
    foreach ($migrations as $migration) {
      foreach (array_chunk($mediaIds, 100) as $chunk) {
        $query = ['include' => implode(',', $chunk), 'per_page' => 100];
        $sourceUrls[] = $migration->getBaseUrl() . '/wp-json/wp/v2/media?' . http_build_query($query);
      }
    }
    $new_migration = $base_plugin_definition;
    $new_migration['source']['urls'] = $sourceUrls;

    $derivative_key = substr($this::MIGRATE_TABLE_NAME, strrpos($this::MIGRATE_TABLE_NAME, '_') + 1);
    $this->derivatives[$derivative_key] = $new_migration;

    return $this->derivatives;
  }

  /**
   * Get a list of all media ids that were stubbed out by previous migrations.
   *
   * @return array
   *   Indexed array of source IDs.
   */
  protected function getMediaIds(): array {
    $prefixLength = strlen($this->database->getPrefix());
    $tableName = mb_substr($this::MIGRATE_TABLE_NAME, 0, 63 - $prefixLength);
    if (!$this->database->schema()->tableExists($tableName)) {
      return [];
    }

    $ids = $this->database->select($tableName, 'm')
      ->fields('m', ['sourceid1', 'sourceid1'])
      ->execute()
      ->fetchAllKeyed();
    return array_keys($ids);
  }

}
