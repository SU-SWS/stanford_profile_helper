<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events_importer\Unit\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Queue\QueueFactory;
use Drupal\Core\Queue\QueueInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Plugin\migrate\id_map\Sql;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
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
   * The database connection mock.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $connection;

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
    $this->connection = $this->createMock(Connection::class);

    $this->subscriber = new EventsImporterSubscriber(
      $this->queueFactory,
      $this->connection
    );
  }

  /**
   * Tests getSubscribedEvents returns correct events.
   */
  public function testGetSubscribedEvents(): void {
    $events = EventsImporterSubscriber::getSubscribedEvents();

    $this->assertArrayHasKey(MigrateEvents::POST_IMPORT, $events);
    $this->assertEquals(['postImport', -100], $events[MigrateEvents::POST_IMPORT]);
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

    $this->connection->expects($this->never())
      ->method('select');

    $this->subscriber->postImport($event);
  }

  /**
   * Tests postImport with empty queue.
   */
  public function testPostImportEmptyQueue(): void {
    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->once())
      ->method('id')
      ->willReturn('stanford_localist_importer');

    $event = $this->createMock(MigrateImportEvent::class);
    $event->expects($this->once())
      ->method('getMigration')
      ->willReturn($migration);

    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->once())
      ->method('numberOfItems')
      ->willReturn(0);

    $this->queueFactory->expects($this->once())
      ->method('get')
      ->with('localist_event_checker')
      ->willReturn($queue);

    $this->connection->expects($this->never())
      ->method('select');

    $this->subscriber->postImport($event);
  }

  /**
   * Tests postImport processes ignored events.
   */
  public function testPostImportProcessesIgnoredEvents(): void {
    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->once())
      ->method('id')
      ->willReturn('stanford_localist_importer');

    $idMap = $this->createMock(Sql::class);
    $idMap->expects($this->once())
      ->method('mapTableName')
      ->willReturn('migrate_map_stanford_localist_importer');

    $migration->expects($this->once())
      ->method('getIdMap')
      ->willReturn($idMap);

    $event = $this->createMock(MigrateImportEvent::class);
    $event->expects($this->exactly(2))
      ->method('getMigration')
      ->willReturn($migration);

    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->once())
      ->method('numberOfItems')
      ->willReturn(5);

    $ignoredItems = [
      '12345' => '67890',
      '23456' => '78901',
      '34567' => '89012',
    ];

    $queue->expects($this->exactly(3))
      ->method('createItem')
      ->willReturnCallback(function ($item) use (&$ignoredItems) {
        $sourceId = (string) $item[0];
        $this->assertArrayHasKey($sourceId, $ignoredItems);
        $this->assertEquals((int) $ignoredItems[$sourceId], $item[1]);
        return TRUE;
      });

    $this->queueFactory->expects($this->once())
      ->method('get')
      ->with('localist_event_checker')
      ->willReturn($queue);

    $statement = $this->createMock(StatementInterface::class);
    $statement->expects($this->once())
      ->method('fetchAllKeyed')
      ->willReturn($ignoredItems);

    $select = $this->createMock(Select::class);
    $select->expects($this->once())
      ->method('fields')
      ->with('m', ['sourceid1', 'destid1'])
      ->willReturnSelf();

    $select->expects($this->once())
      ->method('condition')
      ->with('source_row_status', MigrateIdMapInterface::STATUS_IGNORED)
      ->willReturnSelf();

    $select->expects($this->once())
      ->method('execute')
      ->willReturn($statement);

    $this->connection->expects($this->once())
      ->method('select')
      ->with('migrate_map_stanford_localist_importer', 'map')
      ->willReturn($select);

    $this->subscriber->postImport($event);
  }

  /**
   * Tests postImport with no ignored events.
   */
  public function testPostImportNoIgnoredEvents(): void {
    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->once())
      ->method('id')
      ->willReturn('stanford_localist_importer');

    $idMap = $this->createMock(Sql::class);
    $idMap->expects($this->once())
      ->method('mapTableName')
      ->willReturn('migrate_map_stanford_localist_importer');

    $migration->expects($this->once())
      ->method('getIdMap')
      ->willReturn($idMap);

    $event = $this->createMock(MigrateImportEvent::class);
    $event->expects($this->exactly(2))
      ->method('getMigration')
      ->willReturn($migration);

    $queue = $this->createMock(QueueInterface::class);
    $queue->expects($this->once())
      ->method('numberOfItems')
      ->willReturn(1);

    $queue->expects($this->never())
      ->method('createItem');

    $this->queueFactory->expects($this->once())
      ->method('get')
      ->with('localist_event_checker')
      ->willReturn($queue);

    $statement = $this->createMock(StatementInterface::class);
    $statement->expects($this->once())
      ->method('fetchAllKeyed')
      ->willReturn([]);

    $select = $this->createMock(Select::class);
    $select->expects($this->once())
      ->method('fields')
      ->with('m', ['sourceid1', 'destid1'])
      ->willReturnSelf();

    $select->expects($this->once())
      ->method('condition')
      ->with('source_row_status', MigrateIdMapInterface::STATUS_IGNORED)
      ->willReturnSelf();

    $select->expects($this->once())
      ->method('execute')
      ->willReturn($statement);

    $this->connection->expects($this->once())
      ->method('select')
      ->with('migrate_map_stanford_localist_importer', 'map')
      ->willReturn($select);

    $this->subscriber->postImport($event);
  }

}
