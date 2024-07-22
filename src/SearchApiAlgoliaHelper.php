<?php

namespace Drupal\stanford_profile_helper;

use Algolia\AlgoliaSearch\SearchClient;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Plugin\search_api\datasource\ContentEntity;
use Drupal\search_api_algolia\SearchApiAlgoliaHelper as SapiAlgoliaHelper;

/**
 * Replaces search_api_algolia module service to delete records on delete.
 *
 * The contrib module only schedules the deletion of an item. It doesn't even
 * have a cron job that deletes the records. The contrib module relies on a
 * separate cron task to run drush commands that call the deletion of a record
 * from Algolia. This is unnecessary and ridiculous. Instead, we can delete the
 * records immediately.
 *
 * @codeCoverageIgnore We can't mock guzzle for the search client.
 */
class SearchApiAlgoliaHelper extends SapiAlgoliaHelper {

  /**
   * {@inheritDoc}
   */
  public function entityDelete(EntityInterface $entity) {
    // Check if the entity is a content entity.
    if (!$entity instanceof ContentEntityInterface) {
      return;
    }

    // Get only the algolia indexes.
    $indexes = array_filter(ContentEntity::getIndexesForEntity($entity), fn(IndexInterface $index) => !!$index->getOption('algolia_index_name'));
    foreach ($indexes as $index) {
      $object_id_field = $index->getOption('object_id_field');
      $object_id = $entity->get($object_id_field)->getString();

      $api_server = $index->getServerInstance();

      $app_id = $api_server->getBackendConfig()['application_id'];
      $api_key = $api_server->getBackendConfig()['api_key'];

      // Register the shutdown function for each index.
      drupal_register_shutdown_function([
        self::class,
        'deleteRecords',
      ], $object_id, $index->getOption('algolia_index_name'), $app_id, $api_key);
    }
  }

  /**
   * Execute the deletion on the Algolia system.
   *
   * @param string|int $objet_id
   *   Unique record identifier.
   * @param string $index_name
   *   Algolia index name.
   * @param string $app_id
   *   Algolia App ID.
   * @param string $app_key
   *   Algolia api key with write access.
   */
  public static function deleteRecords(string|int $objet_id, string $index_name, string $app_id, string $app_key) {
    $client = SearchClient::create($app_id, $app_key);
    $index = $client->initIndex($index_name);
    $index->deleteObject($objet_id);
  }

}
