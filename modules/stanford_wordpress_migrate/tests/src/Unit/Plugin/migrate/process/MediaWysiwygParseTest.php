<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\migrate\process;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\migrate\MigrateExecutableInterface;
use Drupal\migrate\Row;
use Drupal\stanford_wordpress_migrate\Plugin\migrate\process\MediaWysiwygParse;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for MediaWysiwygParse process plugin.
 */
class MediaWysiwygParseTest extends UnitTestCase {

  /**
   * The process plugin under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\migrate\process\MediaWysiwygParse
   */
  protected $plugin;

  /**
   * Mock file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fileSystem;

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
   * Mock logger factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->httpClient = $this->createMock(ClientInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->loggerFactory = $this->createMock(LoggerChannelFactoryInterface::class);

    $this->plugin = new MediaWysiwygParse(
      ['image_domain' => 'example.com'],
      'media_wysiwyg_parse',
      [],
      $this->fileSystem,
      $this->httpClient,
      $this->entityTypeManager,
      $this->loggerFactory
    );
  }

  /**
   * Test plugin construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(MediaWysiwygParse::class, $this->plugin);
  }

  /**
   * Test create method.
   */
  public function testCreate(): void {
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->exactly(4))
      ->method('get')
      ->willReturnMap([
        ['file_system', $this->fileSystem],
        ['http_client', $this->httpClient],
        ['entity_type.manager', $this->entityTypeManager],
        ['logger.factory', $this->loggerFactory],
      ]);

    $plugin = MediaWysiwygParse::create(
      $container,
      ['image_domain' => 'example.com'],
      'media_wysiwyg_parse',
      []
    );

    $this->assertInstanceOf(MediaWysiwygParse::class, $plugin);
  }

  /**
   * Test transform method with non-string value.
   */
  public function testTransformNonString(): void {
    $migrateExecutable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->createMock(Row::class);

    $result = $this->plugin->transform(123, $migrateExecutable, $row, 'field_test');
    $this->assertEquals(123, $result);

    $result = $this->plugin->transform([], $migrateExecutable, $row, 'field_test');
    $this->assertEquals([], $result);
  }

  /**
   * Test transform method with simple string.
   */
  public function testTransformSimpleString(): void {
    $migrateExecutable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->createMock(Row::class);

    $value = 'Simple text without media';
    $result = $this->plugin->transform($value, $migrateExecutable, $row, 'field_test');
    $this->assertEquals($value, $result);
  }

  /**
   * Test transform method with iframe.
   */
  public function testTransformWithIframe(): void {
    $migrateExecutable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->createMock(Row::class);

    $mediaStorage = $this->createMock(EntityStorageInterface::class);
    $media = $this->createMock(MediaInterface::class);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('media')
      ->willReturn($mediaStorage);

    $iframe = '<iframe src="https://example.com/embed"></iframe>';
    $mediaStorage->expects($this->once())
      ->method('loadByProperties')
      ->willReturn([$media]);

    $media->expects($this->once())
      ->method('uuid')
      ->willReturn('test-uuid');

    $value = "Some text $iframe more text";
    $result = $this->plugin->transform($value, $migrateExecutable, $row, 'field_test');

    $expectedToken = '<drupal-media data-entity-type="media" data-entity-uuid="test-uuid">&nbsp;</drupal-media>';
    $this->assertStringContainsString($expectedToken, $result);
  }

  /**
   * Test transform method with image without domain match.
   */
  public function testTransformWithImageNoDomainMatch(): void {
    $migrateExecutable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->createMock(Row::class);

    $image = '<img src="https://otherdomain.com/image.jpg" alt="Test image">';
    $value = "Some text $image more text";

    $result = $this->plugin->transform($value, $migrateExecutable, $row, 'field_test');

    // Should not change since domain doesn't match
    $this->assertEquals($value, $result);
  }

  /**
   * Test transform method without image domain configuration.
   */
  public function testTransformWithoutImageDomain(): void {
    $plugin = new MediaWysiwygParse(
      [], // No image_domain configured
      'media_wysiwyg_parse',
      [],
      $this->fileSystem,
      $this->httpClient,
      $this->entityTypeManager,
      $this->loggerFactory
    );

    $migrateExecutable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->createMock(Row::class);

    $image = '<img src="https://example.com/image.jpg" alt="Test image">';
    $value = "Some text $image more text";

    $result = $plugin->transform($value, $migrateExecutable, $row, 'field_test');

    // Should not process images without domain configuration
    $this->assertEquals($value, $result);
  }

  /**
   * Test getMediaTokenFromMarkup method with embed.
   */
  public function testGetMediaTokenFromMarkupEmbed(): void {
    $mediaStorage = $this->createMock(EntityStorageInterface::class);
    $media = $this->createMock(MediaInterface::class);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('media')
      ->willReturn($mediaStorage);

    $iframe = '<iframe src="https://example.com/embed"></iframe>';
    $mediaStorage->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'bundle' => 'embeddable',
        'field_media_embeddable_code' => $iframe,
      ])
      ->willReturn([$media]);

    $media->expects($this->once())
      ->method('uuid')
      ->willReturn('test-uuid');

    $reflection = new \ReflectionClass($this->plugin);
    $method = $reflection->getMethod('getMediaTokenFromMarkup');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->plugin, $iframe);
    $expected = '<drupal-media data-entity-type="media" data-entity-uuid="test-uuid">&nbsp;</drupal-media>';
    $this->assertEquals($expected, $result);
  }

  /**
   * Test transform with multiple iframes.
   */
  public function testTransformWithMultipleIframes(): void {
    $migrateExecutable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->createMock(Row::class);

    $mediaStorage = $this->createMock(EntityStorageInterface::class);
    $media1 = $this->createMock(MediaInterface::class);
    $media2 = $this->createMock(MediaInterface::class);

    $this->entityTypeManager->expects($this->exactly(2))
      ->method('getStorage')
      ->with('media')
      ->willReturn($mediaStorage);

    $iframe1 = '<iframe src="https://example.com/embed1"></iframe>';
    $iframe2 = '<iframe src="https://example.com/embed2"></iframe>';

    $mediaStorage->expects($this->exactly(2))
      ->method('loadByProperties')
      ->willReturnOnConsecutiveCalls([$media1], [$media2]);

    $media1->expects($this->once())
      ->method('uuid')
      ->willReturn('uuid-1');

    $media2->expects($this->once())
      ->method('uuid')
      ->willReturn('uuid-2');

    $value = "Text $iframe1 middle $iframe2 end";
    $result = $this->plugin->transform($value, $migrateExecutable, $row, 'field_test');

    $this->assertStringContainsString('uuid-1', $result);
    $this->assertStringContainsString('uuid-2', $result);
    $this->assertStringNotContainsString('<iframe', $result);
  }

  /**
   * Test transform with exception in iframe processing.
   */
  public function testTransformWithIframeException(): void {
    $migrateExecutable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->createMock(Row::class);

    $mediaStorage = $this->createMock(EntityStorageInterface::class);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('media')
      ->willReturn($mediaStorage);

    $iframe = '<iframe src="https://example.com/embed"></iframe>';
    $mediaStorage->expects($this->once())
      ->method('loadByProperties')
      ->willThrowException(new \Exception('Storage error'));

    $value = "Text $iframe end";
    $result = $this->plugin->transform($value, $migrateExecutable, $row, 'field_test');

    // Should return original value when exception occurs
    $this->assertEquals($value, $result);
  }

  /**
   * Test transform with null return from getMediaTokenFromMarkup.
   */
  public function testTransformWithNullMediaToken(): void {
    $migrateExecutable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->createMock(Row::class);

    // Use plugin without image_domain to test null return
    $plugin = new MediaWysiwygParse(
      [],
      'media_wysiwyg_parse',
      [],
      $this->fileSystem,
      $this->httpClient,
      $this->entityTypeManager,
      $this->loggerFactory
    );

    $image = '<img src="https://example.com/image.jpg">';
    $value = "Text $image end";

    $result = $plugin->transform($value, $migrateExecutable, $row, 'field_test');

    // Should not replace when getMediaTokenFromMarkup returns null
    $this->assertEquals($value, $result);
  }

  /**
   * Test transform with image without alt attribute (existing media).
   */
  public function testTransformImageWithoutAlt(): void {
    $migrateExecutable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->createMock(Row::class);

    $mediaStorage = $this->createMock(EntityStorageInterface::class);
    $fileStorage = $this->createMock(EntityStorageInterface::class);
    $media = $this->createMock(MediaInterface::class);
    $file = $this->createMock(FileInterface::class);

    // Setup file mock
    $file->method('id')->willReturn(123);

    // Setup media mock
    $media->method('uuid')->willReturn('image-uuid');

    // File storage returns existing file
    $fileStorage->method('loadByProperties')->willReturn([$file]);

    // Media storage returns existing media
    $mediaStorage->method('loadByProperties')->willReturn([$media]);

    // Entity type manager returns appropriate storage
    $this->entityTypeManager->method('getStorage')
      ->willReturnCallback(function($type) use ($fileStorage, $mediaStorage) {
        return $type === 'file' ? $fileStorage : $mediaStorage;
      });

    // File system prepareDirectory succeeds
    $this->fileSystem->method('prepareDirectory')->willReturn(TRUE);

    $image = '<img src="https://example.com/image.jpg">';
    $value = "Text $image end";

    $result = $this->plugin->transform($value, $migrateExecutable, $row, 'field_test');

    $this->assertStringContainsString('image-uuid', $result);
    $this->assertStringNotContainsString('<img', $result);
  }

  /**
   * Test transform with complex HTML containing mixed elements.
   */
  public function testTransformComplexHtml(): void {
    $migrateExecutable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->createMock(Row::class);

    $value = '<p>Paragraph</p><img src="https://otherdomain.com/img.jpg"><iframe src="test"></iframe>';

    $mediaStorage = $this->createMock(EntityStorageInterface::class);
    $media = $this->createMock(MediaInterface::class);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->willReturn($mediaStorage);

    $mediaStorage->expects($this->once())
      ->method('loadByProperties')
      ->willReturn([$media]);

    $media->expects($this->once())
      ->method('uuid')
      ->willReturn('embed-uuid');

    $result = $this->plugin->transform($value, $migrateExecutable, $row, 'field_test');

    // Paragraph should remain
    $this->assertStringContainsString('<p>Paragraph</p>', $result);
    // Image should remain (wrong domain)
    $this->assertStringContainsString('otherdomain.com', $result);
    // Iframe should be replaced
    $this->assertStringContainsString('embed-uuid', $result);
  }

  /**
   * Test getMediaTokenFromMarkup returns null when no media created.
   */
  public function testGetMediaTokenFromMarkupReturnsNull(): void {
    $reflection = new \ReflectionClass($this->plugin);
    $method = $reflection->getMethod('getMediaTokenFromMarkup');
    $method->setAccessible(TRUE);

    // Test with image that doesn't match domain
    $image = '<img src="https://wrongdomain.com/image.jpg" alt="Test">';
    $result = $method->invoke($this->plugin, $image);

    $this->assertNull($result);
  }

  /**
   * Test transform with image when file doesn't exist yet (downloads new file).
   */
  public function testTransformImageDownloadNewFile(): void {
    $migrateExecutable = $this->createMock(MigrateExecutableInterface::class);
    $row = $this->createMock(Row::class);

    $mediaStorage = $this->createMock(EntityStorageInterface::class);
    $fileStorage = $this->createMock(EntityStorageInterface::class);
    $media = $this->createMock(MediaInterface::class);
    $file = $this->createMock(FileInterface::class);

    // Track call sequence: getExistingMedia gets file & media storage, downloadFile gets file storage
    $storageCallCount = 0;
    $this->entityTypeManager->expects($this->exactly(4))
      ->method('getStorage')
      ->willReturnCallback(function($type) use ($fileStorage, $mediaStorage, &$storageCallCount) {
        $storageCallCount++;
        if ($type === 'file') {
          return $fileStorage;
        }
        return $mediaStorage;
      });

    $this->fileSystem->expects($this->once())
      ->method('prepareDirectory')
      ->willReturn(TRUE);

    // First call in getExistingMedia - return empty to simulate file doesn't exist
    $fileStorage->expects($this->once())
      ->method('loadByProperties')
      ->willReturn([]);

    // Mock the HTTP client request method
    $this->httpClient->expects($this->once())
      ->method('request')
      ->with(
        'GET',
        $this->stringContains('example.com/newimage.jpg'),
        $this->anything()
      );

    // Create file in downloadFile method
    $fileStorage->expects($this->once())
      ->method('create')
      ->willReturn($file);

    $file->expects($this->once())
      ->method('setPermanent');

    $file->expects($this->once())
      ->method('save');

    $file->expects($this->once())
      ->method('id')
      ->willReturn(456);

    // Create media in getImageMedia method
    $mediaStorage->expects($this->once())
      ->method('create')
      ->willReturn($media);

    $media->expects($this->once())
      ->method('save');

    $media->expects($this->once())
      ->method('uuid')
      ->willReturn('new-image-uuid');

    $image = '<img src="https://example.com/newimage.jpg" alt="New Image">';
    $value = "Text $image end";

    $result = $this->plugin->transform($value, $migrateExecutable, $row, 'field_test');

    $this->assertStringContainsString('new-image-uuid', $result);
    $this->assertStringNotContainsString('<img', $result);
  }

  /**
   * Test getExistingMedia method when file exists but no media.
   */
  public function testGetExistingMediaFileExistsNoMedia(): void {
    $fileStorage = $this->createMock(EntityStorageInterface::class);
    $mediaStorage = $this->createMock(EntityStorageInterface::class);
    $file = $this->createMock(FileInterface::class);

    $this->entityTypeManager->expects($this->exactly(2))
      ->method('getStorage')
      ->willReturnMap([
        ['file', $fileStorage],
        ['media', $mediaStorage],
      ]);

    $fileStorage->expects($this->once())
      ->method('loadByProperties')
      ->with(['uri' => 'public://test.jpg'])
      ->willReturn([$file]);

    $file->expects($this->once())
      ->method('id')
      ->willReturn(123);

    // No media exists for this file
    $mediaStorage->expects($this->once())
      ->method('loadByProperties')
      ->with([
        'bundle' => 'image',
        'field_media_image' => 123,
      ])
      ->willReturn([]);

    $reflection = new \ReflectionClass($this->plugin);
    $method = $reflection->getMethod('getExistingMedia');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->plugin, 'public://test.jpg');
    $this->assertNull($result);
  }

  /**
   * Test downloadFile method.
   */
  public function testDownloadFile(): void {
    $fileStorage = $this->createMock(EntityStorageInterface::class);
    $file = $this->createMock(FileInterface::class);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('file')
      ->willReturn($fileStorage);

    $this->httpClient->expects($this->once())
      ->method('request')
      ->with('GET', 'https://example.com/test.jpg', $this->anything());

    $fileStorage->expects($this->once())
      ->method('create')
      ->with(['uri' => 'public://test.jpg'])
      ->willReturn($file);

    $file->expects($this->once())
      ->method('setPermanent');

    $file->expects($this->once())
      ->method('save');

    $reflection = new \ReflectionClass($this->plugin);
    $method = $reflection->getMethod('downloadFile');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->plugin, 'https://example.com/test.jpg', 'public://test.jpg');
    $this->assertSame($file, $result);
  }

}
