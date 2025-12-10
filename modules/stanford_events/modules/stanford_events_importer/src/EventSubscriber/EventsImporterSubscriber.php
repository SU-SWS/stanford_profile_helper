<?php

declare(strict_types=1);

namespace Drupal\stanford_events_importer\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Events importer event subscriber.
 */
final class EventsImporterSubscriber implements EventSubscriberInterface {

  /**
   * Constructs an EventsImporterSubscriber object.
   */
  public function __construct(
    private readonly QueueFactory $queue,
    private readonly EntityTypeManagerInterface $entityTypeManager
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events[MigrateEvents::POST_IMPORT] = ['postImport', -100];
    return $events;
  }

  /**
   * After migration imports, find ignored events, queue them for review.
   *
   * @param \Drupal\migrate\Event\MigrateImportEvent $event
   *   Triggered event.
   */
  public function postImport(MigrateImportEvent $event): void {
    $queue = $this->queue->get('localist_event_checker');

    if (
      $event->getMigration()->id() != 'stanford_localist_importer' ||
      $queue->numberOfItems() > 0
    ) {
      return;
    }
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $nids = $nodeStorage->getQuery()
      ->accessCheck(FALSE)
      ->condition('su_event_localist_id', 0, '>')
      ->range(0, 50)
      ->execute();

    if (!$nids) {
      return;
    }

    foreach ($nodeStorage->loadMultiple($nids) as $node) {
      $queue->createItem([
        (int) $node->get('su_event_localist_id')?->getString(),
        (int) $node->id(),
      ]);
    }
  }

}
