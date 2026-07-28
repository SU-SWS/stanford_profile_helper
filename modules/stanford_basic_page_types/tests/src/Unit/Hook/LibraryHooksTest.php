<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_basic_page_types\Unit\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_basic_page_types\Hook\LibraryHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for LibraryHooks.
 */
#[Group('stanford_basic_page_types')]
#[CoversClass(LibraryHooks::class)]
class LibraryHooksTest extends UnitTestCase {

  /**
   * The real module directory, used so Finder has real files to iterate.
   *
   * @var string
   */
  protected string $modulePath;

  /**
   * Mocked route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Mocked module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList&\PHPUnit\Framework\MockObject\MockObject
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * The hooks class under test.
   *
   * @var \Drupal\stanford_basic_page_types\Hook\LibraryHooks
   */
  protected LibraryHooks $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->modulePath = dirname(__DIR__, 4);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->moduleExtensionList = $this->createMock(ModuleExtensionList::class);
    $this->hooks = new LibraryHooks($this->routeMatch, $this->moduleExtensionList);
  }

  /**
   * When the current route parameter is not a node, nothing happens.
   */
  public function testPageAttachmentsNotNode(): void {
    $this->routeMatch->method('getParameter')
      ->with('node')
      ->willReturn(NULL);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertSame([], $attachments);
  }

  /**
   * A node that is not the stanford_page bundle gets no library attached.
   */
  public function testPageAttachmentsWrongBundle(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('getType')->willReturn('some_other_type');
    $this->routeMatch->method('getParameter')
      ->with('node')
      ->willReturn($node);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertSame([], $attachments);
  }

  /**
   * A stanford_page node gets the library attached.
   */
  public function testPageAttachmentsStanfordPage(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('getType')->willReturn('stanford_page');
    $this->routeMatch->method('getParameter')
      ->with('node')
      ->willReturn($node);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertSame(
      ['stanford_basic_page_types/node.stanford-page'],
      $attachments['#attached']['library']
    );
  }

  /**
   * The library info is built from real css files found under dist/css.
   */
  public function testLibraryInfoBuild(): void {
    $this->moduleExtensionList->method('getPath')
      ->with('stanford_basic_page_types')
      ->willReturn($this->modulePath);

    $result = $this->hooks->libraryInfoBuild();

    $this->assertArrayHasKey('node.stanford-page', $result);
    $this->assertSame(
      [
        'css' => [
          'component' => [
            'dist/css/component/stanford-page.css' => [],
          ],
        ],
      ],
      $result['node.stanford-page']
    );
  }

}
