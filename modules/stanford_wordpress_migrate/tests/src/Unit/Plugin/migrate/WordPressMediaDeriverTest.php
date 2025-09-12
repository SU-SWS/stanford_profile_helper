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
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressMediaDeriver;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginManager;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Unit tests for WordPressMediaDeriver plugin.
 */
class WordPressMediaDeriverTest extends UnitTestCase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressMediaDeriver
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

    // Mock the database schema.
    $schema = $this->createMock(Schema::class);
    $schema->expects($this->any())
      ->method('tableExists')
      ->willReturn(FALSE);
    $this->database->expects($this->any())
      ->method('schema')
      ->willReturn($schema);

    $container = new Container();
    $container->set('http_client', $this->httpClient);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('entity_field.manager', $this->entityFieldManager);
    $container->set('plugin.manager.wordpress_migrate_field_processor', $this->fieldProcessorPluginManager);
    $container->set('database', $this->database);

    $this->plugin = WordPressMediaDeriver::create($container, 'foo');
  }

  /**
   * Test getDerivativeDefinitions with no migrations.
   */
  public function testGetDerivativeDefinitionsNoMigrations(): void {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn([]);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    $base_definition = [
      'id' => 'wordpress_media',
      'source' => [],
      'destination' => ['plugin' => 'entity:media'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertEmpty($derivatives);
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
    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('media')
      ->willReturn([
        '/wp/v2/media' => [
          'image' => [
            [
              'source' => 'title',
              'destination' => 'name',
            ],
          ],
        ],
      ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn(['migration1' => $migration]);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    $response = $this->createMock(ResponseInterface::class);
    $response->expects($this->once())
      ->method('hasHeader')
      ->with('X-WP-Total')
      ->willReturn(TRUE);
    $response->expects($this->once())
      ->method('getHeaderLine')
      ->with('X-WP-Total')
      ->willReturn('1');

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://example.com/wp-json/wp/v2/media', $this->anything())
      ->willReturn($response);

    // Mock field definitions.
    $fieldDefinition = $this->createMock(\Drupal\Core\Field\FieldDefinitionInterface::class);
    $fieldDefinition->expects($this->any())
      ->method('getType')
      ->willReturn('string');

    $this->entityFieldManager->expects($this->once())
      ->method('getFieldDefinitions')
      ->with('media', 'image')
      ->willReturn(['name' => $fieldDefinition]);

    // Mock field processor plugin.
    $fieldProcessor = $this->createMock(\Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorInterface::class);
    $fieldProcessor->expects($this->once())
      ->method('setWordPressMigration')
      ->with($migration)
      ->willReturnSelf();
    $fieldProcessor->expects($this->once())
      ->method('getProcess')
      ->willReturn(['plugin' => 'get']);
    $fieldProcessor->expects($this->once())
      ->method('getConstants')
      ->willReturn([]);
    $fieldProcessor->expects($this->once())
      ->method('getExtraProcess')
      ->willReturn([]);
    $fieldProcessor->expects($this->any())
      ->method('getMultiplePlugin')
      ->willReturn('get');

    $this->fieldProcessorPluginManager->expects($this->once())
      ->method('getFieldPlugin')
      ->with('string')
      ->willReturn($fieldProcessor);

    $base_definition = [
      'id' => 'wordpress_media',
      'source' => [
        'fields' => [],
        'constants' => [],
      ],
      'process' => [],
      'destination' => ['plugin' => 'entity:media'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertArrayHasKey('media__image', $derivatives);
    $this->assertNotEmpty($derivatives['media__image']['source']['urls']);
  }

  /**
   * Test getDerivativeDefinitions with large number of media IDs (chunking).
   */
  public function testGetDerivativeDefinitionsWithChunking(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');
    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('media')
      ->willReturn([
        '/wp/v2/media' => [
          'image' => [
            [
              'source' => 'title',
              'destination' => 'name',
            ],
          ],
        ],
      ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn(['migration1' => $migration]);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    $response = $this->createMock(ResponseInterface::class);
    $response->expects($this->once())
      ->method('hasHeader')
      ->with('X-WP-Total')
      ->willReturn(TRUE);
    $response->expects($this->once())
      ->method('getHeaderLine')
      ->with('X-WP-Total')
      ->willReturn('150');

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://example.com/wp-json/wp/v2/media', $this->anything())
      ->willReturn($response);

    // Mock field definitions.
    $fieldDefinition = $this->createMock(\Drupal\Core\Field\FieldDefinitionInterface::class);
    $fieldDefinition->expects($this->any())
      ->method('getType')
      ->willReturn('string');

    $this->entityFieldManager->expects($this->once())
      ->method('getFieldDefinitions')
      ->with('media', 'image')
      ->willReturn(['name' => $fieldDefinition]);

    // Mock field processor plugin.
    $fieldProcessor = $this->createMock(\Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorInterface::class);
    $fieldProcessor->expects($this->once())
      ->method('setWordPressMigration')
      ->with($migration)
      ->willReturnSelf();
    $fieldProcessor->expects($this->once())
      ->method('getProcess')
      ->willReturn(['plugin' => 'get']);
    $fieldProcessor->expects($this->once())
      ->method('getConstants')
      ->willReturn([]);
    $fieldProcessor->expects($this->once())
      ->method('getExtraProcess')
      ->willReturn([]);
    $fieldProcessor->expects($this->any())
      ->method('getMultiplePlugin')
      ->willReturn('get');

    $this->fieldProcessorPluginManager->expects($this->once())
      ->method('getFieldPlugin')
      ->with('string')
      ->willReturn($fieldProcessor);

    $base_definition = [
      'id' => 'wordpress_media',
      'source' => [
        'fields' => [],
        'constants' => [],
      ],
      'process' => [],
      'destination' => ['plugin' => 'entity:media'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertArrayHasKey('media__image', $derivatives);
    // Should have 2 URLs (150 IDs / 100 per page = 2 pages)
    $this->assertCount(2, $derivatives['media__image']['source']['urls']);
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
    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('media')
      ->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->any())
      ->method('loadMultiple')
      ->willReturn(['migration1' => $migration]);

    $this->entityTypeManager->expects($this->any())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    $base_definition = [
      'id' => 'wordpress_media',
      'source' => [
        'fields' => [],
        'constants' => [],
      ],
      'process' => [],
      'destination' => ['plugin' => 'entity:media'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertArrayHasKey('media', $derivatives);
  }

}
