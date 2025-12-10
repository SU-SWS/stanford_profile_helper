<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events_importer\Unit\EventSubscriber;

use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
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
   * The database connection mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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

    $this->subscriber = new EventsImporterSubscriber(
      $this->queueFactory,
      $this->entityTypeManager
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

    $ignoredItems = [
      '12345' => '67890',
      '23456' => '78901',
      '34567' => '89012',
    ];

    $queue->expects($this->once())
      ->method('createItem')
      ->willReturnCallback(function($item) use (&$ignoredItems) {
        $sourceId = (string) $item[0];
        $this->assertArrayHasKey($sourceId, $ignoredItems);
        $this->assertEquals((int) $ignoredItems[$sourceId], $item[1]);
        return TRUE;
      });

    $this->queueFactory->expects($this->once())
      ->method('get')
      ->with('localist_event_checker')
      ->willReturn($queue);

    $query = $this->createMock(QueryInterface::class);

    $query->expects($this->once())
      ->method('accessCheck')
      ->with(FALSE)
      ->willReturnSelf();

    $query->expects($this->once())
      ->method('condition')
      ->with('su_event_localist_id', 0, '>')
      ->willReturnSelf();

    $query->expects($this->once())
      ->method('range')
      ->with(0, 50)
      ->willReturnSelf();

    $query->expects($this->once())->method('execute')->willReturn([
      1 => 2,
      3 => 4,
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())->method('getQuery')
      ->willReturn($query);

    $field = $this->createMock(FieldItemListInterface::class);
    $field->expects($this->once())->method('getString')->willReturn(12345);

    $node = $this->createMock(NodeInterface::class);
    $node->expects($this->once())
      ->method('get')
      ->with('su_event_localist_id')
      ->willReturn($field);
    $node->expects($this->once())
      ->method('id')
      ->willReturn(67890);

    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn([67890 => $node]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $this->subscriber->postImport($event);
  }

}
