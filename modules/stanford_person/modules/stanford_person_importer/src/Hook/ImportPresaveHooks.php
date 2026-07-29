<?php

declare(strict_types=1);

namespace Drupal\stanford_person_importer\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\config_pages\ConfigPagesInterface;
use Drupal\node\NodeInterface;

/**
 * Presave hooks supporting the CAP profile importer.
 */
class ImportPresaveHooks {

  /**
   * Import presave hooks constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Core entity type manager service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Implements hook_ENTITY_TYPE_presave().
   *
   * Before saving imported nodes, set the photo field to a default value if
   * it doesn't have any legitimate media items.
   */
  #[Hook('node_presave')]
  public function nodePresave(NodeInterface $entity): void {
    /** @var \Drupal\stanford_migrate\StanfordMigrateInterface $stanford_migrate */
    $stanford_migrate = \Drupal::service('stanford_migrate');
    // Don't worry about nodes that were manually created or if the field is gone.
    if (!$entity->hasField('su_person_photo') || !$stanford_migrate->getEntityMigration($entity)) {
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
        \Drupal::service('stanford_person_importer.cap')
          ->setClientId($entity->get('su_person_cap_username')->getString())
          ->setClientSecret($entity->get('su_person_cap_password')->getString())
          ->updateOrganizations();
      }

      // Invalidate the migration cache since some of the org codes or
      // workgroups probably changed.
      Cache::invalidateTags(['migration_plugins']);
    }
  }

}
