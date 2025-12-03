<?php

declare(strict_types=1);

namespace Drupal\stanford_events_importer\EventSubscriber;

use Drupal\Core\Database\Connection;
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
    private readonly Connection $connection,
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

    /** @var \Drupal\migrate\Plugin\migrate\id_map\Sql $id_map */
    $id_map = $event->getMigration()->getIdMap();

    $items = $this->connection->select($id_map->mapTableName(), 'm')
      ->fields('m', ['sourceid1', 'destid1'])
      ->condition('source_row_status', MigrateIdMapInterface::STATUS_IGNORED)
      ->execute()
      ->fetchAllKeyed();

    foreach ($items as $sourceId => $destId) {
      $queue->createItem([
        (int) $sourceId,
        (int) $destId,
      ]);
    }
  }

}
