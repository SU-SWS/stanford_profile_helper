<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\migrate;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressContentDeriver;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginManager;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Unit tests for WordPressContentDeriver.
 */
class WordPressContentDeriverTest extends UnitTestCase {

  /**
   * The deriver under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressContentDeriver
   */
  protected $deriver;

  /**
   * Mock HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $this->fieldProcessorPluginManager = $this->createMock(WordPressMigrateFieldProcessorPluginManager::class);

    $container = new Container();
    $container->set('http_client', $this->httpClient);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('entity_field.manager', $this->entityFieldManager);
    $container->set('plugin.manager.wordpress_migrate_field_processor', $this->fieldProcessorPluginManager);
    $this->deriver = WordPressContentDeriver::create($container, 'foo');
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

    $base_definition = [
      'id' => 'wordpress_content',
      'source' => [
        'fields' => [],
        'constants' => [],
      ],
      'process' => [],
      'destination' => ['plugin' => 'entity:node'],
    ];

    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertEmpty($derivatives);
  }

  /**
   * Test getDerivativeDefinitions with API failure.
   */
  public function testGetDerivativeDefinitionsApiFailure(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');
    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('node')
      ->willReturn([
        '/wp/v2/posts' => [
          'article' => [],
        ],
      ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(['migration1' => $migration]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException(new \Exception('Connection failed'));

    $base_definition = [
      'id' => 'wordpress_content',
      'source' => [
        'fields' => [],
        'constants' => [],
      ],
      'process' => [],
      'destination' => ['plugin' => 'entity:node'],
    ];

    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertEmpty($derivatives);
  }

  /**
   * Test getDerivativeDefinitions filters out taxonomies.
   */
  public function testGetDerivativeDefinitionsFiltersTaxonomies(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');
    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('taxonomy_term')
      ->willReturn([
        '/wp/v2/categories' => [
          'tags' => [],
        ],
      ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(['migration1' => $migration]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException(new \Exception('Connection failed'));

    $base_definition = [
      'id' => 'wordpress_content',
      'source' => [
        'fields' => [],
        'constants' => [],
      ],
      'process' => [],
      'destination' => ['plugin' => 'entity:taxonomy_term'],
    ];

    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    // Taxonomies should be filtered out, only node content processed
    $this->assertEmpty($derivatives);
  }

  /**
   * Test getDerivativeDefinitions creates derivative ID correctly.
   *
   * This test covers line 66 which generates the derivative ID.
   */
  public function testGetDerivativeDefinitionsCreatesDerivativeId(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');
    $migration->expects($this->any())
      ->method('id')
      ->willReturn('migration1');
    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('node')
      ->willReturn([
        '/wp/v2/posts' => [
          'article' => [
            [
              'source' => 'title',
              'destination' => 'title',
            ],
          ],
        ],
      ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(['migration1' => $migration]);

    $this->entityTypeManager->expects($this->once())
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
      ->with('GET', 'https://example.com/wp-json/wp/v2/posts', $this->anything())
      ->willReturn($response);

    // Mock field definitions.
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $fieldDefinition->expects($this->any())
      ->method('getType')
      ->willReturn('string');

    $this->entityFieldManager->expects($this->once())
      ->method('getFieldDefinitions')
      ->with('node', 'article')
      ->willReturn(['title' => $fieldDefinition]);

    // Mock field processor plugin.
    $fieldProcessor = $this->createMock(WordPressMigrateFieldProcessorInterface::class);
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
      'id' => 'wordpress_content',
      'source' => [
        'fields' => [],
        'constants' => [],
      ],
      'process' => [],
      'destination' => ['plugin' => 'entity:node'],
    ];

    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    // Verify that derivative was created with expected ID format.
    // Line 66: $id = basename($source) . "__$destination";
    // basename('/wp/v2/posts') = 'posts'
    // destination = 'article'
    // Expected ID: 'posts__article'
    $this->assertArrayHasKey('posts__article', $derivatives);
    $this->assertIsArray($derivatives['posts__article']);
    $this->assertEquals('entity:node', $derivatives['posts__article']['destination']['plugin']);
    $this->assertEquals('article', $derivatives['posts__article']['destination']['default_bundle']);
  }

  /**
   * Test getDerivativeDefinitions with duplicate derivative IDs.
   *
   * This test covers lines 71-73 which handle duplicate IDs.
   */
  public function testGetDerivativeDefinitionsDuplicateIds(): void {
    $migration1 = $this->createMock(WordPressMigrationInterface::class);
    $migration1->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration1->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');
    $migration1->expects($this->any())
      ->method('id')
      ->willReturn('migration1');
    $migration1->expects($this->once())
      ->method('getConfigurationValue')
      ->with('node')
      ->willReturn([
        '/wp/v2/posts' => [
          'article' => [
            [
              'source' => 'title',
              'destination' => 'title',
            ],
          ],
        ],
      ]);

    $migration2 = $this->createMock(WordPressMigrationInterface::class);
    $migration2->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration2->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example2.com');
    $migration2->expects($this->any())
      ->method('id')
      ->willReturn('migration2');
    $migration2->expects($this->once())
      ->method('getConfigurationValue')
      ->willReturn([
        '/wp/v2/posts' => [
          'article' => [
            [
              'source' => 'title',
              'destination' => 'title',
            ],
          ],
        ],
      ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn([
        'migration1' => $migration1,
        'migration2' => $migration2,
      ]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    $response = $this->createMock(ResponseInterface::class);
    $response->expects($this->any())
      ->method('hasHeader')
      ->with('X-WP-Total')
      ->willReturn(TRUE);
    $response->expects($this->any())
      ->method('getHeaderLine')
      ->with('X-WP-Total')
      ->willReturn('1');

    $this->httpClient->expects($this->exactly(2))
      ->method('request')
      ->willReturn($response);

    // Mock field definitions.
    $fieldDefinition = $this->createMock(FieldDefinitionInterface::class);
    $fieldDefinition->expects($this->any())
      ->method('getType')
      ->willReturn('string');

    $this->entityFieldManager->expects($this->exactly(2))
      ->method('getFieldDefinitions')
      ->with('node', 'article')
      ->willReturn(['title' => $fieldDefinition]);

    // Mock field processor plugin.
    $fieldProcessor = $this->createMock(WordPressMigrateFieldProcessorInterface::class);
    $fieldProcessor->expects($this->any())
      ->method('setWordPressMigration')
      ->willReturnSelf();
    $fieldProcessor->expects($this->any())
      ->method('getProcess')
      ->willReturn(['plugin' => 'get']);
    $fieldProcessor->expects($this->any())
      ->method('getConstants')
      ->willReturn([]);
    $fieldProcessor->expects($this->any())
      ->method('getExtraProcess')
      ->willReturn([]);
    $fieldProcessor->expects($this->any())
      ->method('getMultiplePlugin')
      ->willReturn('get');

    $this->fieldProcessorPluginManager->expects($this->exactly(2))
      ->method('getFieldPlugin')
      ->with('string')
      ->willReturn($fieldProcessor);

    $base_definition = [
      'id' => 'wordpress_content',
      'source' => [
        'fields' => [],
        'constants' => [],
      ],
      'process' => [],
      'destination' => ['plugin' => 'entity:node'],
    ];

    $derivatives = $this->deriver->getDerivativeDefinitions($base_definition);

    // Verify both derivatives were created.
    // First one: 'posts__article'
    // Second one (duplicate): 'posts__article-migration2' (lines 71-73)
    $this->assertArrayHasKey('posts__article', $derivatives);
    $this->assertArrayHasKey('posts__article-migration2', $derivatives);
  }

}
