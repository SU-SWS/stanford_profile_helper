<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\file\FileInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\layout_library\Entity\Layout;
use Drupal\stanford_profile_helper\LayoutLibraryIcon;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the LayoutLibraryIcon service.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(LayoutLibraryIcon::class)]
class LayoutLibraryIconTest extends UnitTestCase {

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fileSystem;

  /**
   * Mock file usage.
   *
   * @var \Drupal\file\FileUsage\FileUsageInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $fileUsage;

  /**
   * Mock current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account;

  /**
   * Mock module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * Mock UUID service.
   *
   * @var \Drupal\Component\Uuid\UuidInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $uuid;

  /**
   * The service under test.
   *
   * @var \Drupal\stanford_profile_helper\LayoutLibraryIcon
   */
  protected $layoutLibraryIcon;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->fileSystem = $this->createMock(FileSystemInterface::class);
    $this->fileUsage = $this->createMock(FileUsageInterface::class);
    $this->account = $this->createMock(AccountProxyInterface::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->uuid = $this->createMock(UuidInterface::class);

    // Default account ID.
    $this->account->method('id')->willReturn(1);

    $this->layoutLibraryIcon = new LayoutLibraryIcon(
      $this->entityTypeManager,
      $this->fileSystem,
      $this->fileUsage,
      $this->account,
      $this->moduleHandler,
      $this->uuid
    );
  }

  /**
   * Test getLayoutIcon returns existing file when UUID is found.
   */
  public function testGetLayoutIconReturnsExistingFile() {
    $file = $this->createMock(FileInterface::class);

    $layout = $this->createMock(Layout::class);
    $layout->method('getThirdPartySetting')
      ->with('stanford_profile_helper', 'icon', [])
      ->willReturn([
        'uuid' => 'test-uuid-123',
        'data' => 'data:image/png;base64,abc123',
      ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['uuid' => 'test-uuid-123'])
      ->willReturn([$file]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('file')
      ->willReturn($storage);

    $result = $this->layoutLibraryIcon->getLayoutIcon($layout);

    $this->assertSame($file, $result);
  }

  /**
   * Test getLayoutIcon returns default when no icon setting exists.
   */
  public function testGetLayoutIconReturnsDefaultWhenNoSetting() {
    $default_file = $this->createMock(FileInterface::class);

    $layout = $this->createMock(Layout::class);
    $layout->method('getThirdPartySetting')
      ->with('stanford_profile_helper', 'icon', [])
      ->willReturn([]);

    // Mock for getDefaultIcon call.
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->with(['uri' => 'public://layout-icon/default-default-icon.png'])
      ->willReturn([$default_file]);

    $this->entityTypeManager->method('getStorage')
      ->with('file')
      ->willReturn($storage);

    $result = $this->layoutLibraryIcon->getLayoutIcon($layout);

    $this->assertSame($default_file, $result);
  }

  /**
   * Test getDefaultIcon returns existing default file.
   */
  public function testGetDefaultIconReturnsExistingFile() {
    $file = $this->createMock(FileInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['uri' => 'public://layout-icon/default-default-icon.png'])
      ->willReturn([$file]);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('file')
      ->willReturn($storage);

    $result = $this->layoutLibraryIcon->getDefaultIcon();

    $this->assertSame($file, $result);
  }

  /**
   * Test IMAGE_DIRECTORY constant is defined correctly.
   */
  public function testImageDirectoryConstant() {
    $this->assertEquals('public://layout-icon/', LayoutLibraryIcon::IMAGE_DIRECTORY);
  }

  /**
   * Test getLayoutIcon with UUID but no existing file creates new file.
   */
  public function testGetLayoutIconCreatesFileWhenUuidNotFound() {
    $layout = $this->createMock(Layout::class);
    $layout->method('getThirdPartySetting')
      ->with('stanford_profile_helper', 'icon', [])
      ->willReturn([
        'uuid' => 'new-uuid-456',
        'data' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUg',
      ]);
    $layout->method('id')->willReturn('test_layout');

    $new_file = $this->createMock(FileInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    // First call finds no existing file.
    $storage->expects($this->once())
      ->method('loadByProperties')
      ->with(['uuid' => 'new-uuid-456'])
      ->willReturn([]);

    // Second call creates the file.
    $storage->expects($this->once())
      ->method('create')
      ->willReturn($new_file);

    $this->entityTypeManager->method('getStorage')
      ->with('file')
      ->willReturn($storage);

    // Mock file system operations.
    $this->fileSystem->expects($this->once())
      ->method('prepareDirectory')
      ->with(
        'public://layout-icon/',
        FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS
      );

    $this->fileSystem->expects($this->once())
      ->method('saveData')
      ->willReturn('public://layout-icon/test_layout-default-icon.png');

    // Expect file save and usage tracking.
    $new_file->expects($this->once())->method('save');

    $this->fileUsage->expects($this->once())
      ->method('add')
      ->with($new_file, 'layout_library', 'layout', 'test_layout');

    $result = $this->layoutLibraryIcon->getLayoutIcon($layout);

    $this->assertSame($new_file, $result);
  }

  /**
   * Test createFile returns null when saveData fails.
   */
  public function testCreateFileReturnsNullOnSaveFailure() {
    $layout = $this->createMock(Layout::class);
    $layout->method('getThirdPartySetting')
      ->with('stanford_profile_helper', 'icon', [])
      ->willReturn([
        'uuid' => 'fail-uuid',
        'data' => 'data:image/png;base64,abc',
      ]);
    $layout->method('id')->willReturn('fail_layout');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')
      ->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('file')
      ->willReturn($storage);

    // Mock file system to fail saveData.
    $this->fileSystem->method('prepareDirectory');
    $this->fileSystem->expects($this->once())
      ->method('saveData')
      ->willReturn(FALSE);

    $result = $this->layoutLibraryIcon->getLayoutIcon($layout);

    $this->assertNull($result);
  }

  /**
   * Test file entity is created with correct properties.
   */
  public function testFileEntityCreatedWithCorrectProperties() {
    $layout = $this->createMock(Layout::class);
    $layout->method('getThirdPartySetting')
      ->with('stanford_profile_helper', 'icon', [])
      ->willReturn([
        'uuid' => 'props-uuid',
        'data' => 'data:image/png;base64,test',
      ]);
    $layout->method('id')->willReturn('props_layout');

    $new_file = $this->createMock(FileInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);

    $storage->expects($this->once())
      ->method('create')
      ->with($this->callback(function ($values) {
        return $values['uri'] === 'public://layout-icon/props_layout-default-icon.png'
          && $values['uid'] === 1
          && $values['uuid'] === 'props-uuid'
          && $values['status'] === FileInterface::STATUS_PERMANENT;
      }))
      ->willReturn($new_file);

    $this->entityTypeManager->method('getStorage')
      ->with('file')
      ->willReturn($storage);

    $this->fileSystem->method('prepareDirectory');
    $this->fileSystem->method('saveData')
      ->willReturn('public://layout-icon/props_layout-default-icon.png');

    $new_file->expects($this->once())->method('save');

    $result = $this->layoutLibraryIcon->getLayoutIcon($layout);

    $this->assertSame($new_file, $result);
  }

  /**
   * Test SVG file extension is handled correctly.
   */
  public function testSvgFileExtensionHandling() {
    $layout = $this->createMock(Layout::class);
    $layout->method('getThirdPartySetting')
      ->with('stanford_profile_helper', 'icon', [])
      ->willReturn([
        'uuid' => 'svg-uuid',
        'data' => 'data:image/svg+xml;base64,PHN2Zz4=',
      ]);
    $layout->method('id')->willReturn('svg_layout');

    $new_file = $this->createMock(FileInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $storage->method('create')->willReturn($new_file);

    $this->entityTypeManager->method('getStorage')
      ->with('file')
      ->willReturn($storage);

    $this->fileSystem->method('prepareDirectory');
    $this->fileSystem->expects($this->once())
      ->method('saveData')
      ->with(
        $this->anything(),
        $this->stringContains('svg_layout-default-icon.svg')
      )
      ->willReturn('public://layout-icon/svg_layout-default-icon.svg');

    $new_file->expects($this->once())->method('save');

    $result = $this->layoutLibraryIcon->getLayoutIcon($layout);

    $this->assertSame($new_file, $result);
  }

  /**
   * Test file usage is tracked correctly.
   */
  public function testFileUsageTracking() {
    $layout = $this->createMock(Layout::class);
    $layout->method('getThirdPartySetting')
      ->with('stanford_profile_helper', 'icon', [])
      ->willReturn([
        'uuid' => 'usage-uuid',
        'data' => 'data:image/png;base64,test',
      ]);
    $layout->method('id')->willReturn('usage_layout');

    $new_file = $this->createMock(FileInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadByProperties')->willReturn([]);
    $storage->method('create')->willReturn($new_file);

    $this->entityTypeManager->method('getStorage')
      ->with('file')
      ->willReturn($storage);

    $this->fileSystem->method('prepareDirectory');
    $this->fileSystem->method('saveData')
      ->willReturn('public://layout-icon/usage_layout-default-icon.png');

    $new_file->expects($this->once())->method('save');

    $this->fileUsage->expects($this->once())
      ->method('add')
      ->with(
        $new_file,
        'layout_library',
        'layout',
        'usage_layout'
      );

    $this->layoutLibraryIcon->getLayoutIcon($layout);
  }

}
