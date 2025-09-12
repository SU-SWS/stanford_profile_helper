<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\migrate;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Schema;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressFileDeriver;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginManager;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\Core\Database\Stub\Select;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;

/**
 * Unit tests for WordPressFileDeriver plugin.
 */
class WordPressFileDeriverTest extends UnitTestCase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressFileDeriver
   */
  protected $plugin;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityFieldManager;

  /**
   * Mock field processor plugin manager.
   *
   * @var \Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fieldProcessorPluginManager;

  /**
   * Mock HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

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

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $this->fieldProcessorPluginManager = $this->createMock(WordPressMigrateFieldProcessorPluginManager::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->database = $this->createMock(Connection::class);

    $container = new Container();
    $container->set('http_client', $this->httpClient);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('entity_field.manager', $this->entityFieldManager);
    $container->set('plugin.manager.wordpress_migrate_field_processor', $this->fieldProcessorPluginManager);
    $container->set('database', $this->database);

    $this->plugin = WordPressFileDeriver::create($container, 'foo');
  }

  /**
   * Test getDerivativeDefinitions with no migrations.
   */
  public function testGetDerivativeDefinitionsNoMigrations(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn([]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    $schema = $this->createMock(Schema::class);
    $schema->expects($this->any())
      ->method('tableExists')
      ->willReturn(FALSE);

    $this->database->expects($this->any())
      ->method('getPrefix')
      ->willReturn('');

    $this->database->expects($this->any())
      ->method('schema')
      ->willReturn($schema);

    $base_definition = [
      'id' => 'wordpress_files',
      'source' => [],
      'destination' => ['plugin' => 'entity:file'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertArrayNotHasKey('files', $derivatives);
  }

  /**
   * Test getDerivativeDefinitions with media IDs.
   */
  public function testGetDerivativeDefinitionsWithMediaIds(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(['migration1' => $migration]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    $statement = $this->createMock(StatementInterface::class);
    $statement->expects($this->once())
      ->method('fetchAllKeyed')
      ->willReturn([
        '123' => '123',
        '456' => '456',
      ]);

    $selectQuery = $this->createConfiguredStub(Select::class, []);
    $selectQuery->method('fields')
      ->with('m', ['sourceid1', 'sourceid1'])
      ->willReturnSelf();
    $selectQuery->method('execute')
      ->willReturn($statement);

    $this->database->expects($this->once())
      ->method('select')
      ->with('migrate_map_wordpress_files__files', 'm')
      ->willReturn($selectQuery);

    $schema = $this->createMock(Schema::class);
    $schema->expects($this->once())
      ->method('tableExists')
      ->with('migrate_map_wordpress_files__files')
      ->willReturn(TRUE);

    $this->database->expects($this->once())
      ->method('getPrefix')
      ->willReturn('');

    $this->database->expects($this->once())
      ->method('schema')
      ->willReturn($schema);

    $base_definition = [
      'id' => 'wordpress_files',
      'source' => [],
      'destination' => ['plugin' => 'entity:file'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertArrayHasKey('files', $derivatives);
    $this->assertArrayHasKey('urls', $derivatives['files']['source']);
    $this->assertNotEmpty($derivatives['files']['source']['urls']);
    $this->assertStringContainsString('wp-json/wp/v2/media', $derivatives['files']['source']['urls'][0]);
    $this->assertStringContainsString('include=123%2C456', $derivatives['files']['source']['urls'][0]);
    $this->assertStringContainsString('per_page=100', $derivatives['files']['source']['urls'][0]);
  }

  /**
   * Test getDerivativeDefinitions with table name truncation.
   */
  public function testGetDerivativeDefinitionsTableNameTruncation(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(['migration1' => $migration]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    $schema = $this->createMock(Schema::class);
    $schema->expects($this->once())
      ->method('tableExists')
      ->willReturn(FALSE);

    $this->database->expects($this->once())
      ->method('getPrefix')
      ->willReturn('very_long_prefix_');

    $this->database->expects($this->once())
      ->method('schema')
      ->willReturn($schema);

    $base_definition = [
      'id' => 'wordpress_files',
      'source' => [],
      'destination' => ['plugin' => 'entity:file'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertArrayHasKey('files', $derivatives);
  }

  /**
   * Test getDerivativeDefinitions with empty media IDs.
   */
  public function testGetDerivativeDefinitionsEmptyMediaIds(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(['migration1' => $migration]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    $statement = $this->createMock(StatementInterface::class);
    $statement->expects($this->once())
      ->method('fetchAllKeyed')
      ->willReturn([]);

    $selectQuery = $this->createConfiguredStub(
      Select::class,
      []
    );
    $selectQuery->method('fields')
      ->with('m', ['sourceid1', 'sourceid1'])
      ->willReturnSelf();
    $selectQuery->method('execute')
      ->willReturn($statement);

    $this->database->expects($this->once())
      ->method('select')
      ->with('migrate_map_wordpress_files__files', 'm')
      ->willReturn($selectQuery);

    $schema = $this->createMock(Schema::class);
    $schema->expects($this->once())
      ->method('tableExists')
      ->with('migrate_map_wordpress_files__files')
      ->willReturn(TRUE);

    $this->database->expects($this->once())
      ->method('getPrefix')
      ->willReturn('');

    $this->database->expects($this->once())
      ->method('schema')
      ->willReturn($schema);

    $base_definition = [
      'id' => 'wordpress_files',
      'source' => [],
      'destination' => ['plugin' => 'entity:file'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertArrayHasKey('files', $derivatives);
  }

}
