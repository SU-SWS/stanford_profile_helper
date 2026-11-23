<?php

declare(strict_types=1);

namespace Drupal\stanford_person_importer\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\config_pages\ConfigPagesInterface;
use Drupal\migrate\Plugin\MigrationPluginManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_migrate\StanfordMigrateInterface;
use Drupal\stanford_person_importer\CapInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Presave hooks supporting the CAP profile importer.
 */
class ImportPresaveHooks {

  /**
   * Import presave hooks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Core entity type manager service.
   * @param \Drupal\stanford_migrate\StanfordMigrateInterface $stanfordMigrate
   *   Stanford migrate service.
   * @param \Drupal\stanford_person_importer\CapInterface $cap
   *   CAP API service.
   * @param \Drupal\migrate\Plugin\MigrationPluginManagerInterface $migrationManager
   *   Migration plugin manager service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StanfordMigrateInterface $stanfordMigrate,
    #[Autowire(service: 'stanford_person_importer.cap')]
    protected CapInterface $cap,
    protected MigrationPluginManagerInterface $migrationManager,
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_presave().
   *
   * Before saving imported nodes, set the photo field to a default value if
   * it doesn't have any legitimate media items.
   */
  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $entity): void {
    // Don't worry about nodes that were manually created or if the field is
    // gone.
    if (!$entity->hasField('su_person_photo') || !$this->stanfordMigrate->getEntityMigration($entity)) {
      return;
    }
    $photo_values = $entity->get('su_person_photo')->getValue();
    $media_storage = $this->entityTypeManager->getStorage('media');
    foreach ($photo_values as $value) {
      // If any delta value has a valid media entity, we don't need to set the
      // default field value.
      if ($media_storage->load($value['target_id'])) {
        return;
      }
    }

    $default_photo = $entity->getFieldDefinition('su_person_photo')
      ->getDefaultValue($entity);
    // Set the default value of the photo field.
    $entity->set('su_person_photo', $default_photo);
  }

  /**
   * Implements hook_ENTITY_TYPE_presave().
   */
  #[Hook('config_pages_presave')]
  public function configPagesPresave(ConfigPagesInterface $entity): void {
    if ($entity->bundle() == 'stanford_person_importer') {
      $terms = $this->entityTypeManager
        ->getStorage('taxonomy_term')
        ->getQuery()
        ->accessCheck(FALSE)
        ->condition('vid', 'cap_org_codes')
        ->execute();

      // No org codes exist, lets load them up.
      if (empty($terms)) {
        $this->cap
          ->setClientId($entity->get('su_person_cap_username')->getString())
          ->setClientSecret($entity->get('su_person_cap_password')->getString())
          ->updateOrganizations();
      }

      // The config page overrides the migration status, which decides if the
      // migration plugin exists, so the definitions have to be rebuilt.
      $this->migrationManager->clearCachedDefinitions();
    }
  }

}
