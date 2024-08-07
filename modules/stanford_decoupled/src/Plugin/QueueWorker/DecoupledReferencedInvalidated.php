<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\QueueWorkerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'decoupled_referenced_invalidated' queue worker.
 *
 * @codeCoverageIgnore since it calls an external api, we can't test.
 *
 * @QueueWorker(
 *   id = "decoupled_referenced_invalidator",
 *   title = @Translation("Decoupled Referenced Invalidator"),
 *   cron = {"time" = 60},
 * )
 */
final class DecoupledReferencedInvalidated extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new DecoupledReferencedInvalidated instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    if ($this->entityTypeManager->hasDefinition($data['entity_type'])) {
      $entity = $this->entityTypeManager->getStorage($data['entity_type'])
        ->load($data['id']);
      if ($entity) {
        next_entity_update($entity);
      }
    }
  }

}
