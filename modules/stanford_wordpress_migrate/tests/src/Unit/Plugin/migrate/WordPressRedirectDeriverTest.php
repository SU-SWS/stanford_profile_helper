<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\migrate;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressRedirectDeriver;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginManager;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Unit tests for WordPressRedirectDeriver plugin.
 */
class WordPressRedirectDeriverTest extends UnitTestCase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressRedirectDeriver
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
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityFieldManager = $this->createMock(EntityFieldManagerInterface::class);
    $this->fieldProcessorPluginManager = $this->createMock(WordPressMigrateFieldProcessorPluginManager::class);
    $this->httpClient = $this->createMock(ClientInterface::class);

    $container = new Container();
    $container->set('http_client', $this->httpClient);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $container->set('entity_field.manager', $this->entityFieldManager);
    $container->set('plugin.manager.wordpress_migrate_field_processor', $this->fieldProcessorPluginManager);

    $this->plugin = WordPressRedirectDeriver::create($container, 'foo');
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
      'id' => 'wordpress_redirect',
      'source' => [],
      'migration_dependencies' => ['required' => []],
      'process' => [
        'redirect_source' => [],
      ],
      'destination' => ['plugin' => 'entity:redirect'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertEmpty($derivatives);
  }

  /**
   * Test getDerivativeDefinitions with node mappings.
   */
  public function testGetDerivativeDefinitionsWithNodes(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');
    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('node', [])
      ->willReturn([
        '/wp/v2/posts' => [
          'article' => ['field_mappings'],
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
      ->willReturn('75');

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://example.com/wp-json/wp/v2/posts', $this->anything())
      ->willReturn($response);

    $base_definition = [
      'id' => 'wordpress_redirect',
      'source' => [],
      'migration_dependencies' => ['required' => []],
      'process' => [
        'redirect_source' => [],
      ],
      'destination' => ['plugin' => 'entity:redirect'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertArrayHasKey('posts__article', $derivatives);
    $this->assertNotEmpty($derivatives['posts__article']['source']['urls']);
    $this->assertStringContainsString('wp-json/wp/v2/posts', $derivatives['posts__article']['source']['urls'][0]);
    $this->assertEquals(['wordpress_content:posts__article'], $derivatives['posts__article']['migration_dependencies']['required']);
    $this->assertEquals('https://example.com', $derivatives['posts__article']['process']['redirect_source']['search']);
  }

  /**
   * Test getDerivativeDefinitions with multiple destinations.
   */
  public function testGetDerivativeDefinitionsMultipleDestinations(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');
    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('node', [])
      ->willReturn([
        '/wp/v2/posts' => [
          'article' => ['field_mappings1'],
          'page' => ['field_mappings2'],
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
      ->willReturn('50');

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $base_definition = [
      'id' => 'wordpress_redirect',
      'source' => [],
      'migration_dependencies' => ['required' => []],
      'process' => [
        'redirect_source' => [],
      ],
      'destination' => ['plugin' => 'entity:redirect'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertCount(2, $derivatives);
    $this->assertArrayHasKey('posts__article', $derivatives);
    $this->assertArrayHasKey('posts__page', $derivatives);
  }

  /**
   * Test getDerivativeDefinitions when API request fails.
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
      ->with('node', [])
      ->willReturn([
        '/wp/v2/posts' => [
          'article' => ['field_mappings'],
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
      'id' => 'wordpress_redirect',
      'source' => [],
      'migration_dependencies' => ['required' => []],
      'process' => [
        'redirect_source' => [],
      ],
      'destination' => ['plugin' => 'entity:redirect'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertEmpty($derivatives);
  }

  /**
   * Test getDerivativeDefinitions with duplicate derivative IDs.
   */
  public function testGetDerivativeDefinitionsDuplicateIds(): void {
    $migration1 = $this->createMock(WordPressMigrationInterface::class);
    $migration1->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration1->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example1.com');
    $migration1->expects($this->any())
      ->method('id')
      ->willReturn('migration1');
    $migration1->expects($this->once())
      ->method('getConfigurationValue')
      ->with('node', [])
      ->willReturn([
        '/wp/v2/posts' => [
          'article' => ['field_mappings'],
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
      ->with('node', [])
      ->willReturn([
        '/wp/v2/posts' => [
          'article' => ['field_mappings'],
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
    $response->expects($this->exactly(2))
      ->method('hasHeader')
      ->with('X-WP-Total')
      ->willReturn(TRUE);
    $response->expects($this->exactly(2))
      ->method('getHeaderLine')
      ->with('X-WP-Total')
      ->willReturnOnConsecutiveCalls('10', '20');

    $this->httpClient->expects($this->exactly(2))
      ->method('request')
      ->willReturn($response);

    $base_definition = [
      'id' => 'wordpress_redirect',
      'source' => [],
      'migration_dependencies' => ['required' => []],
      'process' => [
        'redirect_source' => [],
      ],
      'destination' => ['plugin' => 'entity:redirect'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertCount(2, $derivatives);
    // First one gets normal ID
    $this->assertArrayHasKey('posts__article', $derivatives);
    // Second one gets migration ID appended
    $this->assertArrayHasKey('posts__article-migration2', $derivatives);
  }

  /**
   * Test getDerivativeDefinitions with empty node config.
   */
  public function testGetDerivativeDefinitionsEmptyNodeConfig(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');
    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('node', [])
      ->willReturn([]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn(['migration1' => $migration]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    $base_definition = [
      'id' => 'wordpress_redirect',
      'source' => [],
      'migration_dependencies' => ['required' => []],
      'process' => [
        'redirect_source' => [],
      ],
      'destination' => ['plugin' => 'entity:redirect'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertEmpty($derivatives);
  }

  /**
   * Test getDerivativeDefinitions with response missing header.
   */
  public function testGetDerivativeDefinitionsNoTotalHeader(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->any())
      ->method('isPublished')
      ->willReturn(TRUE);
    $migration->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');
    $migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('node', [])
      ->willReturn([
        '/wp/v2/posts' => [
          'article' => ['field_mappings'],
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
      ->willReturn(FALSE);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $base_definition = [
      'id' => 'wordpress_redirect',
      'source' => [],
      'migration_dependencies' => ['required' => []],
      'process' => [
        'redirect_source' => [],
      ],
      'destination' => ['plugin' => 'entity:redirect'],
    ];

    $derivatives = $this->plugin->getDerivativeDefinitions($base_definition);

    $this->assertIsArray($derivatives);
    $this->assertEmpty($derivatives);
  }

}
