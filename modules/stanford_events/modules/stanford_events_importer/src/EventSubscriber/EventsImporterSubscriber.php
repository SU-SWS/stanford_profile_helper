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
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $database
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
    $query = $this->database->select($event->getMigration()
      ->getIdMap()
      ->getQualifiedMapTableName(), 'map')
      ->fields('map', ['destid1', 'sourceid1'])
      ->condition('source_row_status', MigrateIdMapInterface::STATUS_IGNORED)
      ->orderBy('last_imported', 'ASC')
      ->execute();
    while ($row = $query->fetchAssoc()) {
      $this->queueNode((int) $row['destid1'], (int) $row['sourceid1']);
    }
  }

  /**
   * Queue the given node to be check if it needs to be cleared.
   *
   * @param int $nid
   * @param int $instanceId
   *
   * @return void
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function queueNode(int $nid, int $instanceId): void {
    $queue = $this->queue->get('localist_event_checker');
    $node = $this->entityTypeManager->getStorage('node')->load($nid);

    if (
      !$node->hasField('su_event_localist_id') ||
      !$node->get('su_event_localist_id')->count()
    ) {
      return;
    }

    $queue->createItem([
      (int) $node->get('su_event_localist_id')?->getString(),
      $nid,
      $instanceId,
    ]);
  }

}
