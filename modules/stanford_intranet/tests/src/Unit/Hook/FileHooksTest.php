<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_intranet\Unit\Hook;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\file\FileInterface;
use Drupal\file\FileRepositoryInterface;
use Drupal\file\FileUsage\FileUsageInterface;
use Drupal\stanford_intranet\Hook\FileHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for FileHooks.
 */
#[Group('stanford_intranet')]
#[CoversClass(FileHooks::class)]
class FileHooksTest extends UnitTestCase {

  /**
   * Mocked state service.
   *
   * @var \Drupal\Core\State\StateInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected StateInterface $state;

  /**
   * Mocked module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * The container builder used to stub \Drupal::service() calls.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected ContainerBuilder $container;

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_intranet\Hook\FileHooks
   */
  protected FileHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->state = $this->createMock(StateInterface::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->hooks = new FileHooks($this->state, $this->moduleHandler);

    $this->container = new ContainerBuilder();
    \Drupal::setContainer($this->container);
  }

  /**
   * Sets the mocked file.repository service on the container.
   */
  protected function setFileRepository($return): FileRepositoryInterface {
    $repository = $this->createMock(FileRepositoryInterface::class);
    $repository->method('loadByUri')->willReturn($return);
    $this->container->set('file.repository', $repository);
    return $repository;
  }

  /**
   * Sets the mocked file.usage service on the container.
   */
  protected function setFileUsage(array $return): FileUsageInterface {
    $usage = $this->createMock(FileUsageInterface::class);
    $usage->method('listUsage')->willReturn($return);
    $this->container->set('file.usage', $usage);
    return $usage;
  }

  /**
   * Converted PNG images on the private scheme are re-dispatched to the
   * original uri via the module handler.
   */
  public function testFileDownloadPrivatePngRedispatchesToOriginalUri() {
    $uri = 'private://styles/thumbnail/private/image.jpg.png';

    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('file_download', ['private://styles/thumbnail/private/image.jpg'])
      ->willReturn(['result' => TRUE]);

    $result = $this->hooks->fileDownload($uri);
    $this->assertSame(['result' => TRUE], $result);
  }

  /**
   * Also matches the ".jpeg.png" extension pattern.
   */
  public function testFileDownloadPrivateJpegPngRedispatchesToOriginalUri() {
    $uri = 'private://foo/image.jpeg.png';

    $this->moduleHandler->expects($this->once())
      ->method('invokeAll')
      ->with('file_download', ['private://foo/image.jpeg'])
      ->willReturn([]);

    $result = $this->hooks->fileDownload($uri);
    $this->assertSame([], $result);
  }

  /**
   * A ".png" converted file on a non-private scheme does not get
   * re-dispatched and instead continues through the normal file lookup.
   */
  public function testFileDownloadNonPrivateSchemeDoesNotRedispatch() {
    $uri = 'public://foo/image.jpg.png';

    $this->moduleHandler->expects($this->never())->method('invokeAll');
    $this->setFileRepository(NULL);

    $result = $this->hooks->fileDownload($uri);
    $this->assertNull($result);
  }

  /**
   * When the file cannot be loaded by uri, nothing is returned.
   */
  public function testFileDownloadFileNotFound() {
    $uri = 'public://foo/bar.txt';
    $this->setFileRepository(NULL);

    $result = $this->hooks->fileDownload($uri);
    $this->assertNull($result);
  }

  /**
   * Files not referenced by media entities return their download headers.
   */
  public function testFileDownloadReturnsHeadersWhenNotUsedByMedia() {
    $uri = 'public://foo/bar.txt';

    $file = $this->createMock(FileInterface::class);
    $file->method('getDownloadHeaders')->willReturn(['Content-Type' => 'text/plain']);
    $this->setFileRepository($file);
    $this->setFileUsage([]);

    $result = $this->hooks->fileDownload($uri);
    $this->assertSame(['Content-Type' => 'text/plain'], $result);
  }

  /**
   * Files referenced by media entities fall through to normal access
   * checks, so this hook implementation returns nothing.
   */
  public function testFileDownloadReturnsNothingWhenUsedByMedia() {
    $uri = 'public://foo/bar.txt';

    $file = $this->createMock(FileInterface::class);
    $file->expects($this->never())->method('getDownloadHeaders');
    $this->setFileRepository($file);
    $this->setFileUsage(['file' => ['media' => [1 => 1]]]);

    $result = $this->hooks->fileDownload($uri);
    $this->assertNull($result);
  }

  /**
   * Access is allowed when every condition is satisfied.
   */
  public function testFileAccessAllowed() {
    $file = $this->createMock(FileInterface::class);
    $this->setFileUsage(['file' => ['config_pages' => [1 => 1]]]);

    $this->state->method('get')
      ->with('stanford_intranet', FALSE)
      ->willReturn(TRUE);

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(TRUE);

    $result = $this->hooks->fileAccess($file, 'download', $account);
    $this->assertTrue($result->isAllowed());
  }

  /**
   * Access is neutral (not forbidden) when the intranet is disabled.
   */
  public function testFileAccessNeutralWhenIntranetDisabled() {
    $file = $this->createMock(FileInterface::class);
    $this->setFileUsage(['file' => ['config_pages' => [1 => 1]]]);

    $this->state->method('get')
      ->with('stanford_intranet', FALSE)
      ->willReturn(FALSE);

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(TRUE);

    $result = $this->hooks->fileAccess($file, 'download', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Access is neutral for anonymous (non-authenticated) users.
   */
  public function testFileAccessNeutralWhenNotAuthenticated() {
    $file = $this->createMock(FileInterface::class);
    $this->setFileUsage(['file' => ['config_pages' => [1 => 1]]]);

    $this->state->method('get')
      ->with('stanford_intranet', FALSE)
      ->willReturn(TRUE);

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(FALSE);

    $result = $this->hooks->fileAccess($file, 'download', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Access is neutral when the file is not used by a config page.
   */
  public function testFileAccessNeutralWhenNotConfigPageUsage() {
    $file = $this->createMock(FileInterface::class);
    $this->setFileUsage(['file' => ['media' => [1 => 1]]]);

    $this->state->method('get')
      ->with('stanford_intranet', FALSE)
      ->willReturn(TRUE);

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(TRUE);

    $result = $this->hooks->fileAccess($file, 'download', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Access is neutral for operations other than "download" or "view".
   */
  public function testFileAccessNeutralForOtherOperation() {
    $file = $this->createMock(FileInterface::class);
    $this->setFileUsage(['file' => ['config_pages' => [1 => 1]]]);

    $this->state->method('get')
      ->with('stanford_intranet', FALSE)
      ->willReturn(TRUE);

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(TRUE);

    $result = $this->hooks->fileAccess($file, 'update', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * The "view" operation is also allowed, matching "download".
   */
  public function testFileAccessAllowedForViewOperation() {
    $file = $this->createMock(FileInterface::class);
    $this->setFileUsage(['file' => ['config_pages' => [1 => 1]]]);

    $this->state->method('get')
      ->with('stanford_intranet', FALSE)
      ->willReturn(TRUE);

    $account = $this->createMock(AccountInterface::class);
    $account->method('isAuthenticated')->willReturn(TRUE);

    $result = $this->hooks->fileAccess($file, 'view', $account);
    $this->assertTrue($result->isAllowed());
  }

}
