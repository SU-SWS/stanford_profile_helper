<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events_importer\Unit\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_events_importer\EventSubscriber\EventsImporterSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for EventsImporterSubscriber.
 */
#[Group('stanford_events_importer')]
#[CoversClass(EventsImporterSubscriber::class)]
class EventsImporterSubscriberTest extends UnitTestCase {

  /**
   * The queue factory mock.
   *
   * @var \Drupal\Core\Queue\QueueFactory|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $queueFactory;

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection mock.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * The event subscriber.
   *
   * @var \Drupal\stanford_events_importer\EventSubscriber\EventsImporterSubscriber
   */
  protected $subscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->queueFactory = $this->createMock(QueueFactory::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->database = $this->createMock(Connection::class);

    $this->subscriber = new EventsImporterSubscriber(
      $this->queueFactory,
      $this->entityTypeManager,
      $this->database
    );
  }

  /**
   * Tests getSubscribedEvents returns correct events.
   */
  public function testGetSubscribedEvents(): void {
    $events = EventsImporterSubscriber::getSubscribedEvents();

    $this->assertArrayHasKey(MigrateEvents::POST_IMPORT, $events);
    $this->assertEquals([
      'postImport',
      -100,
    ], $events[MigrateEvents::POST_IMPORT]);
  }

  /**
   * Tests postImport with wrong migration ID.
   */
  public function testPostImportWrongMigration(): void {
    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->once())
      ->method('id')
      ->willReturn('some_other_migration');

    $event = $this->createMock(MigrateImportEvent::class);
    $event->expects($this->once())
      ->method('getMigration')
      ->willReturn($migration);

    $queue = $this->createMock(QueueInterface::class);

    $this->queueFactory->expects($this->once())
      ->method('get')
      ->with('localist_event_checker')
      ->willReturn($queue);

    $this->entityTypeManager->expects($this->never())
      ->method('getStorage');

    $this->subscriber->postImport($event);
  }

  /**
   * Tests postImport with non-empty queue.
   */
  public function testPostImportNonEmptyQueue(): void {
    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->once())
      ->method('id')
      ->willReturn('stanford_localist_importer');

    $event = $this->createMock(MigrateImportEvent::class);
    $event->expects($this->any())
      ->method('getMigration')
      ->willReturn($migration);

    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->once())
      ->method('numberOfItems')
      ->willReturn(1);

    $this->queueFactory->expects($this->once())
      ->method('get')
      ->with('localist_event_checker')
      ->willReturn($queue);

    $this->entityTypeManager->expects($this->never())
      ->method('getStorage');

    $this->subscriber->postImport($event);
  }

  /**
   * Tests postImport processes ignored events.
   */
  public function testPostImportProcessesIgnoredEvents(): void {
    $idMap = $this->createMock(Sql::class);
    $idMap->expects($this->once())
      ->method('getQualifiedMapTableName')
      ->willReturn('migrate_map_stanford_localist_importer');

    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->once())
      ->method('id')
      ->willReturn('stanford_localist_importer');
    $migration->expects($this->once())
      ->method('getIdMap')
      ->willReturn($idMap);

    $event = $this->createMock(MigrateImportEvent::class);
    $event->expects($this->any())
      ->method('getMigration')
      ->willReturn($migration);

    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->once())
      ->method('numberOfItems')
      ->willReturn(0);
    $queue->expects($this->exactly(2))
      ->method('createItem')
      ->willReturn(TRUE);

    $this->queueFactory->expects($this->any())
      ->method('get')
      ->with('localist_event_checker')
      ->willReturn($queue);

    $statement = $this->createMock(StatementInterface::class);
    $statement->expects($this->exactly(3))
      ->method('fetchAssoc')
      ->willReturnOnConsecutiveCalls(
        ['destid1' => '100', 'sourceid1' => '12345'],
        ['destid1' => '200', 'sourceid1' => '23456'],
        FALSE
      );

    $dbQuery = $this->createMock(Select::class);
    $dbQuery->expects($this->once())
      ->method('fields')
      ->with('map', ['destid1', 'sourceid1'])
      ->willReturnSelf();
    $dbQuery->expects($this->once())
      ->method('condition')
      ->with('source_row_status', MigrateIdMapInterface::STATUS_IGNORED)
      ->willReturnSelf();
    $dbQuery->expects($this->once())
      ->method('orderBy')
      ->with('last_imported', 'ASC')
      ->willReturnSelf();
    $dbQuery->expects($this->once())
      ->method('execute')
      ->willReturn($statement);

    $this->database->expects($this->once())
      ->method('select')
      ->with('migrate_map_stanford_localist_importer', 'map')
      ->willReturn($dbQuery);

    $field = $this->createMock(FieldItemListInterface::class);
    $field->expects($this->exactly(2))
      ->method('getString')
      ->willReturnOnConsecutiveCalls('67890', '78901');
    $field->expects($this->exactly(2))
      ->method('count')
      ->willReturn(1);

    $node1 = $this->createMock(NodeInterface::class);
    $node1->expects($this->once())
      ->method('hasField')
      ->with('su_event_localist_id')
      ->willReturn(TRUE);
    $node1->expects($this->exactly(2))
      ->method('get')
      ->with('su_event_localist_id')
      ->willReturn($field);

    $node2 = $this->createMock(NodeInterface::class);
    $node2->expects($this->once())
      ->method('hasField')
      ->with('su_event_localist_id')
      ->willReturn(TRUE);
    $node2->expects($this->exactly(2))
      ->method('get')
      ->with('su_event_localist_id')
      ->willReturn($field);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->exactly(2))
      ->method('load')
      ->willReturnOnConsecutiveCalls($node1, $node2);

    $this->entityTypeManager->expects($this->exactly(2))
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $this->subscriber->postImport($event);
  }

}
