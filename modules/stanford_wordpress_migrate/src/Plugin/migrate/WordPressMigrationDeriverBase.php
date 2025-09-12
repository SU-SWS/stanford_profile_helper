<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginManager;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use function Symfony\Component\String\u;

/**
 * WordPress migration plugin deriver base.
 */
abstract class WordPressMigrationDeriverBase extends DeriverBase implements ContainerDeriverInterface {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('http_client'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.wordpress_migrate_field_processor')
    );
  }

  /**
   * Content migration plugin deriver constructor.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   Guzzler client service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity Type Manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager service.
   */
  public function __construct(
    protected ClientInterface $client,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected WordPressMigrateFieldProcessorPluginManager $fieldProcessorPluginManager,
  ) {}

  /**
   * Build the migration derivative configuration for the given entity type.
   *
   * @param array $base_plugin_definition
   *   Base migration definition.
   * @param string|null $entityType
   *    Entity type id.
   *
   * @return array
   *   Derivative definitions.
   */
  protected function buildContentDerivativeDefinitions(array $base_plugin_definition, ?string $entityType = NULL): array {
    $entityType = $entityType ?: str_replace('entity:', '', $base_plugin_definition['destination']['plugin']);
    foreach ($this->getWordPressMigrations() as $migration) {
      $entityMappings = $migration->getConfigurationValue($entityType, []);

      foreach ($entityMappings as $source => $destinations) {
        $sourceUrls = $this->getSourceUrls($migration->getBaseUrl() . "/wp-json$source");
        if (!$sourceUrls) {
          continue;
        }

        foreach ($destinations as $destination => $field_mapping) {
          $id = basename($source) . "__$destination";
          $destFields = $this->entityFieldManager->getFieldDefinitions($entityType, $destination);

          // If two migration settings map the same source to destination, make
          // sure we have separate migrations for each.
          if (isset($this->derivatives[$id])) {
            $id .= '-' . $migration->id();
          }

          $new_migration = $base_plugin_definition;
          $new_migration['destination'] = [
            'plugin' => 'entity:' . $entityType,
            'default_bundle' => $destination,
          ];
          $new_migration['source']['urls'] = $sourceUrls;

          foreach ($field_mapping as $delta => $field) {
            $destField = $destFields[preg_replace('/\/.*$/', '', $field['destination'])] ?? FALSE;
            if (!$destField) {
              continue;
            }
            $plugin = $this->fieldProcessorPluginManager->getFieldPlugin($destField->getType())
              ?->setWordPressMigration($migration);
            if (!$plugin) {
              continue;
            }

            $sourceField = u($field['source'])->snake()->toString();
            $new_migration['source']['fields'][$sourceField] = [
              'name' => $sourceField,
              'label' => $sourceField,
              'selector' => $field['source'],
            ];

            // Put the delta in front so later sorting step doesn't change the
            // order from that of the UI.
            $key = '_' . u(sprintf('%s__%s__%s', $delta, $sourceField, $field['destination']))
                ->snake()
                ->toString();
            $column = preg_replace('/^.*?\/(.*)/', '$1', $field['destination']) ?: NULL;
            $process = $field['settings'] ?? $plugin->getProcess($destField, $column);
            if (!$process) {
              continue;
            }

            if (!isset($new_migration['source']['constants'])) {
              $new_migration['source']['constants'] = [];
            }
            $new_migration['source']['constants'] += $plugin->getConstants();

            if (isset($process['plugin'])) {
              $process['source'] = $sourceField;
            }
            else {
              $process[0]['source'] = $sourceField;
            }

            $new_migration['process'][$key] = $process;
            $new_migration['process'] += $plugin->getExtraProcess($destField);

            $dest = $field['destination'];
            $new_migration['process'][$dest][0]['plugin'] = $plugin->getMultiplePlugin($destField);

            if (isset($new_migration['process'][$dest][0]['source']) && !is_array($new_migration['process'][$dest][0]['source'])) {
              $new_migration['process'][$dest][0]['source'] = [$new_migration['process'][$dest][0]['source']];
            }

            $new_migration['process'][$dest][0]['source'][] = "@$key";

            if (
              count($new_migration['process'][$dest][0]['source']) > 1 &&
              $plugin->getMultiplePlugin($destField) == 'get'
            ) {
              $new_migration['process'][$dest][1] = ['plugin' => 'flatten'];
            }
          }
          ksort($new_migration['process']);

          // Now that we know how source to destination fields, go through
          // the list of processes and remove any null_coalesce, get, concat,
          // etc. processes if there's only 1 source value.
          foreach ($new_migration['process'] as $key => &$destProcess) {
            if (
              !str_starts_with($key, '_') &&
              isset($destProcess[0]['source']) &&
              is_array($destProcess[0]['source']) &&
              count($destProcess[0]['source']) == 1
            ) {
              $sourceKey = str_replace('@', '', $destProcess[0]['source'][0]);
              $destProcess = $new_migration['process'][$sourceKey];
              unset($new_migration['process'][$sourceKey]);
            }
          }

          $new_migration['source']['fields'] = array_values($new_migration['source']['fields']);
          $this->derivatives[$id] = $new_migration;
        }
      }
    }
    return $this->derivatives;
  }

  /**
   * Get all migration entities that are published.
   *
   * @return \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface[]
   *   Associative array of entity ids to entity.
   */
  protected function getWordPressMigrations(): array {
    $migration_storage = $this->entityTypeManager->getStorage('wordpress_migration');
    /** @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration */
    return array_filter($migration_storage->loadMultiple(), fn($migration) => $migration->isPublished());
  }

  /**
   * Get a list of API urls for the WordPress site.
   *
   * @param string $baseApi
   *   Base api url.
   *
   * @return array
   *   Indexed array of API urls.
   */
  protected function getSourceUrls(string $baseApi, $filterQuery = []): array {
    try {
      $response = $this->client->request('GET', $baseApi, [
        'query' => [
          ...$filterQuery,
          'per_page' => 1,
        ],
      ]);
    }
    catch (\Throwable $e) {
      return [];
    }
    if (!$response->hasHeader('X-WP-Total')) {
      return [];
    }
    $total_count = $response->getHeaderLine('X-WP-Total');
    $total_pages = (int) ceil($total_count / 100);
    $urls = [];
    for ($page = 1; $page <= $total_pages; $page++) {
      $query = $filterQuery;
      $query['per_page'] = 100;
      $query['page'] = $page;
      $urls[] = $baseApi . "?" . http_build_query($query);
    }

    return $urls;
  }

}
