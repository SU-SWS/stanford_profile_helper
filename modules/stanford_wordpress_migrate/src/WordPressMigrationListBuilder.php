<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Provides a list controller for the WordPress migration entity type.
 *
 * @codeCoverageIgnore
 */
class WordPressMigrationListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    $header['base_url'] = $this->t('Site URL');
    $header['status'] = $this->t('Status');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $entity */
    $row['label'] = $entity->label();
    $row['base_url'] = $entity->getBaseUrl();
    $row['status'] = $entity->isPublished() ? $this->t('Enabled') : $this->t('Disabled');
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function getDefaultOperations(EntityInterface $entity) {
    /** @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $entity */
    $operations = parent::getDefaultOperations($entity);
    $operations['edit']['url'] = new Url('entity.wordpress_migration.edit_form', [
      'machine_name' => $entity->id(),
      'step' => 'source',
    ]);

    if (!$entity->isPublished() && $entity->hasLinkTemplate('enable')) {
      $operations['enable'] = [
        'title' => $this->t('Enable'),
        'weight' => -10,
        'url' => $this->ensureDestination($entity->toUrl('enable')),
      ];
    }
    elseif ($entity->hasLinkTemplate('disable')) {
      $operations['disable'] = [
        'title' => $this->t('Disable'),
        'weight' => 40,
        'url' => $this->ensureDestination($entity->toUrl('disable')),
      ];
    }

    return $operations;
  }

}
