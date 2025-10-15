<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\entity_usage\EntityUsageBulkInterface;
use Drupal\next\Event\EntityActionEvent;
use Drupal\next\Event\EntityActionEventInterface;
use Drupal\stanford_decoupled\Config\DecoupledConfigOverrides;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Entity crud tracking hooks to invalidate caches.
 */
class StanfordDecoupledEntityTrackHooks {

  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
    #[Autowire(service: 'entity_usage.usage')]
    protected EntityUsageBulkInterface $entityUsage,
  ) {}

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  #[Hook('entity_update')]
  #[Hook('entity_delete')]
  public function onEntityCrud(EntityInterface $entity) {
    $tracked_types = $this->configFactory->get('stanford_decoupled.settings')
      ->get('referenced_invalidated_types') ?: ['taxonomy_term', 'media'];

    if (
      !in_array($entity->getEntityTypeId(), $tracked_types) ||
      !$entity instanceof ContentEntityInterface ||
      !DecoupledConfigOverrides::isDecoupled()
    ) {
      return;
    }
    foreach ($this->entityUsage->listSources($entity) as $entity_type => $sources) {
      $storage = $this->entityTypeManager->getStorage($entity_type);

      switch ($entity_type) {
        case 'node':
          /** @var \Drupal\node\NodeInterface $node */
          foreach ($storage->loadMultiple(array_keys($sources)) as $node) {
            self::entityUpdate($node);
          }
          break;

        case 'paragraph':
          /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
          foreach ($storage->loadMultiple(array_keys($sources)) as $paragraph) {
            if ($paragraph->getParentEntity()?->getEntityTypeId() == 'node') {
              self::entityUpdate($paragraph->getParentEntity());
            }
          }
          break;
      }
    }
  }

  /**
   * Trigger Next.js cache invalidation for the given entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity to invalidate.
   */
  protected static function entityUpdate(ContentEntityInterface $entity): void {
    if (\Drupal::hasService('next.entity_action_event_dispatcher')) {
      $event = EntityActionEvent::createFromEntity($entity, EntityActionEventInterface::UPDATE_ACTION);
      \Drupal::service('next.entity_action_event_dispatcher')->addEvent($event);
    }
  }

}
