<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\migrate\Event\MigrateEvents;
use Drupal\migrate\Event\MigrateImportEvent;
use Drupal\migrate\Event\MigrateRollbackEvent;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\stanford_wordpress_migrate\EventSubscriber\WordPressMigrateSubscriber;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Unit tests for WordPressMigrateSubscriber.
 */
class WordPressMigrateSubscriberTest extends UnitTestCase {

  /**
   * The event subscriber under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\EventSubscriber\WordPressMigrateSubscriber
   */
  protected $subscriber;

  /**
   * Mock migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $migrationPluginManager;

  /**
   * Mock file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fileSystem;

  /**
   * Mock database connection.
   *
   * @var \Drupal\Core\Database\Connection|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->migrationPluginManager = $this->createMock(MigrationPluginManager::class);
    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->database = $this->createMock(Connection::class);
    $this->subscriber = new WordPressMigrateSubscriber(
      $this->migrationPluginManager,
      $this->fileSystem,
      $this->database
    );
  }

  /**
   * Test the subscriber implements EventSubscriberInterface.
   */
  public function testImplementsInterface(): void {
    $this->assertInstanceOf(EventSubscriberInterface::class, $this->subscriber);
  }

  /**
   * Test getSubscribedEvents method.
   */
  public function testGetSubscribedEvents(): void {
    $events = WordPressMigrateSubscriber::getSubscribedEvents();

    $this->assertIsArray($events);
    $this->assertArrayHasKey(MigrateEvents::POST_IMPORT, $events);
    $this->assertArrayHasKey(MigrateEvents::POST_ROLLBACK, $events);

    $this->assertEquals(['onPostMigrateImport'], $events[MigrateEvents::POST_IMPORT]);
    $this->assertEquals(['onPostMigrateRollback'], $events[MigrateEvents::POST_ROLLBACK]);
  }

  /**
   * Test onPostMigrateImport method.
   */
  public function testOnPostMigrateImport(): void {
    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->exactly(2))
      ->method('id')
      ->willReturn('wordpress_content:test_migration');

    $event = $this->createMock(MigrateImportEvent::class);
    $event->expects($this->exactly(2))
      ->method('getMigration')
      ->willReturn($migration);

    // The method calls flushMigrationPlugins which calls clearCachedDefinitions
    // only if the migration ID starts with 'wordpress_content:'
    $this->migrationPluginManager->expects($this->once())
      ->method('clearCachedDefinitions');

    $this->subscriber->onPostMigrateImport($event);
  }

  /**
   * Test onPostMigrateRollback method.
   */
  public function testOnPostMigrateRollback(): void {
    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->once())
      ->method('id')
      ->willReturn('wordpress_content:test_migration');

    $event = $this->createMock(MigrateRollbackEvent::class);
    $event->expects($this->once())
      ->method('getMigration')
      ->willReturn($migration);

    // The method calls flushMigrationPlugins which calls clearCachedDefinitions
    $this->migrationPluginManager->expects($this->once())
      ->method('clearCachedDefinitions');

    $this->subscriber->onPostMigrateRollback($event);
  }

  /**
   * Test constructor dependency injection.
   */
  public function testConstruct(): void {
    $subscriber = new WordPressMigrateSubscriber(
      $this->migrationPluginManager,
      $this->fileSystem,
      $this->database
    );
    $this->assertInstanceOf(WordPressMigrateSubscriber::class, $subscriber);
  }

  /**
   * Test flushMigrationPlugins method is called correctly.
   */
  public function testFlushMigrationPluginsCallsCorrectMethods(): void {
    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->exactly(3))
      ->method('id')
      ->willReturn('wordpress_content:test');

    $importEvent = $this->createMock(MigrateImportEvent::class);
    $importEvent->expects($this->exactly(2))
      ->method('getMigration')
      ->willReturn($migration);

    $rollbackEvent = $this->createMock(MigrateRollbackEvent::class);
    $rollbackEvent->expects($this->once())
      ->method('getMigration')
      ->willReturn($migration);

    $this->migrationPluginManager->expects($this->exactly(2))
      ->method('clearCachedDefinitions');

    // Test both event handlers
    $this->subscriber->onPostMigrateImport($importEvent);
    $this->subscriber->onPostMigrateRollback($rollbackEvent);
  }

  /**
   * Test onPostMigrateImport calls deleteStubbedFiles for wordpress_files:files.
   */
  public function testOnPostMigrateImportDeletesStubbedFiles(): void {
    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->exactly(2))
      ->method('id')
      ->willReturn('wordpress_files:files');

    $event = $this->createMock(MigrateImportEvent::class);
    $event->expects($this->exactly(2))
      ->method('getMigration')
      ->willReturn($migration);

    // Simulate stubbed files found in the public directory.
    $this->fileSystem->expects($this->once())
      ->method('scanDirectory')
      ->with('public://', '/.*/', ['recurse' => FALSE])
      ->willReturn([
        'public://stubbed_file' => (object) ['uri' => 'public://stubbed_file'],
        'public://valid_file.txt' => (object) ['uri' => 'public://valid_file.txt'],
      ]);

    $statement = $this->createMock(StatementInterface::class);
    $statement->expects($this->once())
      ->method('fetchAllKeyed')
      ->willReturn([
        1 => 'public://valid_file.txt',
      ]);

    $query = $this->createMock(SelectInterface::class);
    $query->expects($this->once())
      ->method('fields')
      ->with('f', ['fid', 'uri'])
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('execute')
      ->willReturn($statement);

    $this->database->expects($this->once())
      ->method('select')
      ->with('file_managed', 'f')
      ->willReturn($query);

    // Expect unlink to be called for stubbed file.
    // Suppress warnings from file system functions in unit tests.
    $this->fileSystem->expects($this->once())
      ->method('unlink')
      ->with('public://stubbed_file');

    @$this->subscriber->onPostMigrateImport($event);
  }

  /**
   * Test onPostMigrateImport does not call deleteStubbedFiles for other migrations.
   */
  public function testOnPostMigrateImportSkipsDeleteForOtherMigrations(): void {
    $migration = $this->createMock(MigrationInterface::class);
    $migration->expects($this->exactly(2))
      ->method('id')
      ->willReturn('wordpress_content:test');

    $event = $this->createMock(MigrateImportEvent::class);
    $event->expects($this->exactly(2))
      ->method('getMigration')
      ->willReturn($migration);

    $this->migrationPluginManager->expects($this->once())
      ->method('clearCachedDefinitions');

    $this->fileSystem->expects($this->never())
      ->method('scanDirectory');

    $this->database->expects($this->never())
      ->method('select');

    $this->subscriber->onPostMigrateImport($event);
  }

}
