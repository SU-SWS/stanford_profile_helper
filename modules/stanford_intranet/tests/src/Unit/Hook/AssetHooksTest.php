<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_intranet\Unit\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\State\StateInterface;
use Drupal\stanford_intranet\Hook\AssetHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for AssetHooks.
 */
#[Group('stanford_intranet')]
#[CoversClass(AssetHooks::class)]
class AssetHooksTest extends UnitTestCase {

  /**
   * Mocked state service.
   *
   * @var \Drupal\Core\State\StateInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected StateInterface $state;

  /**
   * Mocked admin route context service.
   *
   * @var \Drupal\Core\Routing\AdminContext&\PHPUnit\Framework\MockObject\MockObject
   */
  protected AdminContext $adminContext;

  /**
   * Mocked module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList&\PHPUnit\Framework\MockObject\MockObject
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_intranet\Hook\AssetHooks
   */
  protected AssetHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->state = $this->createMock(StateInterface::class);
    $this->adminContext = $this->createMock(AdminContext::class);
    $this->moduleExtensionList = $this->createMock(ModuleExtensionList::class);
    $this->hooks = new AssetHooks($this->state, $this->adminContext, $this->moduleExtensionList);
  }

  /**
   * The intranet library is attached when the intranet is on and the route
   * is not an admin route.
   */
  public function testPageAttachmentsAddsLibrary() {
    $this->state->method('get')
      ->with('stanford_intranet', FALSE)
      ->willReturn(TRUE);
    $this->adminContext->method('isAdminRoute')->willReturn(FALSE);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertSame(['stanford_intranet/intranet'], $attachments['#attached']['library']);
  }

  /**
   * No library is attached when the intranet is disabled.
   */
  public function testPageAttachmentsNoLibraryWhenIntranetDisabled() {
    $this->state->method('get')
      ->with('stanford_intranet', FALSE)
      ->willReturn(FALSE);
    $this->adminContext->method('isAdminRoute')->willReturn(FALSE);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertArrayNotHasKey('#attached', $attachments);
  }

  /**
   * No library is attached on admin routes even when intranet is on.
   */
  public function testPageAttachmentsNoLibraryOnAdminRoute() {
    $this->state->method('get')
      ->with('stanford_intranet', FALSE)
      ->willReturn(TRUE);
    $this->adminContext->method('isAdminRoute')->willReturn(TRUE);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertArrayNotHasKey('#attached', $attachments);
  }

  /**
   * The library info build walks the module's real dist/css directory and
   * derives library definitions from the discovered CSS files.
   */
  public function testLibraryInfoBuild() {
    $module_path = dirname(__DIR__, 4);
    $this->assertFileExists("$module_path/dist/css");

    $this->moduleExtensionList->method('getPath')
      ->with('stanford_intranet')
      ->willReturn($module_path);

    $libraries = $this->hooks->libraryInfoBuild();

    $this->assertNotEmpty($libraries);
    $this->assertArrayHasKey('intranet', $libraries);
    $this->assertArrayHasKey('base', $libraries['intranet']['css']);
    $this->assertArrayHasKey(
      'dist/css/base/intranet.css',
      $libraries['intranet']['css']['base']
    );

    $this->assertArrayHasKey('stanford_intranet.config', $libraries);
  }

  /**
   * When no CSS files are found, an empty library array is returned.
   */
  public function testLibraryInfoBuildNoFilesFound() {
    $module_path = sys_get_temp_dir() . '/stanford_intranet_empty_' . uniqid();
    mkdir("$module_path/dist/css", 0777, TRUE);

    $this->moduleExtensionList->method('getPath')
      ->with('stanford_intranet')
      ->willReturn($module_path);

    $libraries = $this->hooks->libraryInfoBuild();

    $this->assertSame([], $libraries);

    // Clean up the temporary fixture directory.
    @rmdir("$module_path/dist/css");
    @rmdir("$module_path/dist");
    @rmdir($module_path);
  }

}
