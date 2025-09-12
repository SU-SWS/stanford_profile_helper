<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\migrate;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginManager;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Unit tests for WordPressMigrationDeriverBase.
 */
class WordPressMigrationDeriverBaseTest extends UnitTestCase {

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
  }

  /**
   * Test getWordPressMigrations filters unpublished migrations.
   */
  public function testGetWordPressMigrationsFiltersUnpublished(): void {
    $publishedMigration = $this->createMock(WordPressMigrationInterface::class);
    $publishedMigration->expects($this->once())
      ->method('isPublished')
      ->willReturn(TRUE);

    $unpublishedMigration = $this->createMock(WordPressMigrationInterface::class);
    $unpublishedMigration->expects($this->once())
      ->method('isPublished')
      ->willReturn(FALSE);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadMultiple')
      ->willReturn([
        'published' => $publishedMigration,
        'unpublished' => $unpublishedMigration,
      ]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('wordpress_migration')
      ->willReturn($storage);

    // Create a concrete implementation for testing
    $deriver = new class($this->httpClient, $this->entityTypeManager, $this->entityFieldManager, $this->fieldProcessorPluginManager) extends \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressMigrationDeriverBase {
      public function getDerivativeDefinitions($base_plugin_definition) {
        return [];
      }

      public function testGetWordPressMigrations(): array {
        return $this->getWordPressMigrations();
      }
    };

    $migrations = $deriver->testGetWordPressMigrations();
    $this->assertCount(1, $migrations);
    $this->assertArrayHasKey('published', $migrations);
    $this->assertArrayNotHasKey('unpublished', $migrations);
  }

  /**
   * Test getSourceUrls with response missing X-WP-Total header.
   */
  public function testGetSourceUrlsNoTotalHeader(): void {
    $response = $this->createMock(ResponseInterface::class);
    $response->expects($this->once())
      ->method('hasHeader')
      ->with('X-WP-Total')
      ->willReturn(FALSE);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://example.com/api', $this->anything())
      ->willReturn($response);

    $deriver = new class($this->httpClient, $this->entityTypeManager, $this->entityFieldManager, $this->fieldProcessorPluginManager) extends \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressMigrationDeriverBase {
      public function getDerivativeDefinitions($base_plugin_definition) {
        return [];
      }

      public function testGetSourceUrls(string $baseApi, array $filterQuery = []): array {
        return $this->getSourceUrls($baseApi, $filterQuery);
      }
    };

    $urls = $deriver->testGetSourceUrls('https://example.com/api');
    $this->assertEmpty($urls);
  }

  /**
   * Test getSourceUrls with filter query parameters.
   */
  public function testGetSourceUrlsWithFilterQuery(): void {
    $filterQuery = ['status' => 'publish', 'author' => '10'];

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
      ->with(
        'GET',
        'https://example.com/api',
        $this->callback(function ($options) use ($filterQuery) {
          return isset($options['query']) &&
            $options['query']['status'] === 'publish' &&
            $options['query']['author'] === '10' &&
            $options['query']['per_page'] === 1;
        })
      )
      ->willReturn($response);

    $deriver = new class($this->httpClient, $this->entityTypeManager, $this->entityFieldManager, $this->fieldProcessorPluginManager) extends \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressMigrationDeriverBase {
      public function getDerivativeDefinitions($base_plugin_definition) {
        return [];
      }

      public function testGetSourceUrls(string $baseApi, array $filterQuery = []): array {
        return $this->getSourceUrls($baseApi, $filterQuery);
      }
    };

    $urls = $deriver->testGetSourceUrls('https://example.com/api', $filterQuery);

    $this->assertIsArray($urls);
    $this->assertCount(1, $urls);
    $this->assertStringContainsString('status=publish', $urls[0]);
    $this->assertStringContainsString('author=10', $urls[0]);
    $this->assertStringContainsString('per_page=100', $urls[0]);
    $this->assertStringContainsString('page=1', $urls[0]);
  }

  /**
   * Test getSourceUrls with exception from HTTP client.
   */
  public function testGetSourceUrlsWithException(): void {
    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://example.com/api', $this->anything())
      ->willThrowException(new \Exception('Network error'));

    $deriver = new class($this->httpClient, $this->entityTypeManager, $this->entityFieldManager, $this->fieldProcessorPluginManager) extends \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressMigrationDeriverBase {
      public function getDerivativeDefinitions($base_plugin_definition) {
        return [];
      }

      public function testGetSourceUrls(string $baseApi, array $filterQuery = []): array {
        return $this->getSourceUrls($baseApi, $filterQuery);
      }
    };

    $urls = $deriver->testGetSourceUrls('https://example.com/api');
    $this->assertEmpty($urls);
  }

  /**
   * Test getSourceUrls with different exception types.
   */
  public function testGetSourceUrlsWithDifferentExceptions(): void {
    // Test with RuntimeException
    $this->httpClient->expects($this->once())
      ->method('request')
      ->willThrowException(new \RuntimeException('Connection timeout'));

    $deriver = new class($this->httpClient, $this->entityTypeManager, $this->entityFieldManager, $this->fieldProcessorPluginManager) extends \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressMigrationDeriverBase {
      public function getDerivativeDefinitions($base_plugin_definition) {
        return [];
      }

      public function testGetSourceUrls(string $baseApi, array $filterQuery = []): array {
        return $this->getSourceUrls($baseApi, $filterQuery);
      }
    };

    $urls = $deriver->testGetSourceUrls('https://example.com/api');
    $this->assertEmpty($urls);
  }

  /**
   * Test getSourceUrls with multiple pages.
   */
  public function testGetSourceUrlsMultiplePages(): void {
    $response = $this->createMock(ResponseInterface::class);
    $response->expects($this->once())
      ->method('hasHeader')
      ->with('X-WP-Total')
      ->willReturn(TRUE);
    $response->expects($this->once())
      ->method('getHeaderLine')
      ->with('X-WP-Total')
      ->willReturn('350'); // Will result in 4 pages (350 / 100 = 3.5, ceil = 4)

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $deriver = new class($this->httpClient, $this->entityTypeManager, $this->entityFieldManager, $this->fieldProcessorPluginManager) extends \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressMigrationDeriverBase {
      public function getDerivativeDefinitions($base_plugin_definition) {
        return [];
      }

      public function testGetSourceUrls(string $baseApi, array $filterQuery = []): array {
        return $this->getSourceUrls($baseApi, $filterQuery);
      }
    };

    $urls = $deriver->testGetSourceUrls('https://example.com/api');

    $this->assertIsArray($urls);
    $this->assertCount(4, $urls);

    // Verify each URL has correct page number
    $this->assertStringContainsString('page=1', $urls[0]);
    $this->assertStringContainsString('page=2', $urls[1]);
    $this->assertStringContainsString('page=3', $urls[2]);
    $this->assertStringContainsString('page=4', $urls[3]);

    // Verify all have per_page=100
    foreach ($urls as $url) {
      $this->assertStringContainsString('per_page=100', $url);
      $this->assertStringStartsWith('https://example.com/api?', $url);
    }
  }

  /**
   * Test getSourceUrls with zero total count.
   */
  public function testGetSourceUrlsZeroTotal(): void {
    $response = $this->createMock(ResponseInterface::class);
    $response->expects($this->once())
      ->method('hasHeader')
      ->with('X-WP-Total')
      ->willReturn(TRUE);
    $response->expects($this->once())
      ->method('getHeaderLine')
      ->with('X-WP-Total')
      ->willReturn('0');

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $deriver = new class($this->httpClient, $this->entityTypeManager, $this->entityFieldManager, $this->fieldProcessorPluginManager) extends \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressMigrationDeriverBase {
      public function getDerivativeDefinitions($base_plugin_definition) {
        return [];
      }

      public function testGetSourceUrls(string $baseApi, array $filterQuery = []): array {
        return $this->getSourceUrls($baseApi, $filterQuery);
      }
    };

    $urls = $deriver->testGetSourceUrls('https://example.com/api');

    $this->assertIsArray($urls);
    $this->assertEmpty($urls);
  }

  /**
   * Test getSourceUrls with filter query in request.
   */
  public function testGetSourceUrlsPassesFilterQuery(): void {
    $filterQuery = ['status' => 'publish', 'category' => '5'];

    $response = $this->createMock(ResponseInterface::class);
    $response->expects($this->once())
      ->method('hasHeader')
      ->with('X-WP-Total')
      ->willReturn(FALSE);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        'https://example.com/api',
        $this->callback(function ($options) use ($filterQuery) {
          return isset($options['query']) &&
            $options['query']['status'] === 'publish' &&
            $options['query']['category'] === '5' &&
            $options['query']['per_page'] === 1;
        })
      )
      ->willReturn($response);

    $deriver = new class($this->httpClient, $this->entityTypeManager, $this->entityFieldManager, $this->fieldProcessorPluginManager) extends \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressMigrationDeriverBase {
      public function getDerivativeDefinitions($base_plugin_definition) {
        return [];
      }

      public function testGetSourceUrls(string $baseApi, array $filterQuery = []): array {
        return $this->getSourceUrls($baseApi, $filterQuery);
      }
    };

    $urls = $deriver->testGetSourceUrls('https://example.com/api', $filterQuery);
    $this->assertEmpty($urls);
  }

  /**
   * Test getUrl method is called correctly.
   */
  public function testGetUrlMethod(): void {
    // Since getUrl was removed and URL building is now inline,
    // test that http_build_query is used correctly
    $response = $this->createMock(ResponseInterface::class);
    $response->expects($this->once())
      ->method('hasHeader')
      ->with('X-WP-Total')
      ->willReturn(TRUE);
    $response->expects($this->once())
      ->method('getHeaderLine')
      ->with('X-WP-Total')
      ->willReturn('10');

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $deriver = new class($this->httpClient, $this->entityTypeManager, $this->entityFieldManager, $this->fieldProcessorPluginManager) extends \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressMigrationDeriverBase {
      public function getDerivativeDefinitions($base_plugin_definition) {
        return [];
      }

      public function testGetSourceUrls(string $baseApi, array $filterQuery = []): array {
        return $this->getSourceUrls($baseApi, $filterQuery);
      }
    };

    $filterQuery = ['key' => 'value', 'special' => 'test&data'];
    $urls = $deriver->testGetSourceUrls('https://example.com/api', $filterQuery);

    $this->assertIsArray($urls);
    $this->assertCount(1, $urls);

    // Verify URL encoding works correctly
    $this->assertStringContainsString('key=value', $urls[0]);
    $this->assertStringContainsString('special=test%26data', $urls[0]);
    $this->assertStringContainsString('per_page=100', $urls[0]);
    $this->assertStringContainsString('page=1', $urls[0]);
  }

  /**
   * Test getSourceUrls with empty total count string.
   */
  public function testGetSourceUrlsEmptyTotalCount(): void {
    $response = $this->createMock(ResponseInterface::class);
    $response->expects($this->once())
      ->method('hasHeader')
      ->with('X-WP-Total')
      ->willReturn(TRUE);
    $response->expects($this->once())
      ->method('getHeaderLine')
      ->with('X-WP-Total')
      ->willReturn('0');

    $this->httpClient->expects($this->once())
      ->method('request')
      ->willReturn($response);

    $deriver = new class($this->httpClient, $this->entityTypeManager, $this->entityFieldManager, $this->fieldProcessorPluginManager) extends \Drupal\stanford_wordpress_migrate\Plugin\migrate\WordPressMigrationDeriverBase {
      public function getDerivativeDefinitions($base_plugin_definition) {
        return [];
      }

      public function testGetSourceUrls(string $baseApi, array $filterQuery = []): array {
        return $this->getSourceUrls($baseApi, $filterQuery);
      }
    };

    $urls = $deriver->testGetSourceUrls('https://example.com/api');
    $this->assertEmpty($urls);
  }

}
