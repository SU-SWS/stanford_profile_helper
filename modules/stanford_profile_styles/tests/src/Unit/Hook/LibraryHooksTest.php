<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_styles\Unit\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\AdminContext;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\editor\Entity\Editor;
use Drupal\filter\FilterFormatInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_profile_styles\Hook\LibraryHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for LibraryHooks.
 */
#[Group('stanford_profile_styles')]
#[CoversClass(LibraryHooks::class)]
class LibraryHooksTest extends UnitTestCase {

  /**
   * The real module root directory, used for libraryInfoBuild fixtures.
   *
   * @var string
   */
  protected string $modulePath;

  /**
   * Mock admin context service.
   *
   * @var \Drupal\Core\Routing\AdminContext|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $adminContext;

  /**
   * Mock route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeMatch;

  /**
   * Mock module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleExtensionList;

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_styles\Hook\LibraryHooks
   */
  protected LibraryHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->modulePath = dirname(__DIR__, 4);

    $this->adminContext = $this->createMock(AdminContext::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->moduleExtensionList = $this->createMock(ModuleExtensionList::class);

    $this->hooks = new LibraryHooks($this->adminContext, $this->routeMatch, $this->moduleExtensionList);
  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    putenv('CI');
    parent::tearDown();
  }

  /**
   * Admin routes get no libraries attached at all.
   */
  public function testPageAttachmentsAdminRoute(): void {
    $this->adminContext->method('isAdminRoute')->willReturn(TRUE);
    $this->routeMatch->expects($this->never())->method('getParameter');

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertArrayNotHasKey('#attached', $attachments);
  }

  /**
   * Non admin route without a node route parameter gets the three base
   * libraries only.
   */
  public function testPageAttachmentsNonAdminNoNode(): void {
    $this->adminContext->method('isAdminRoute')->willReturn(FALSE);
    $this->routeMatch->method('getParameter')->with('node')->willReturn(NULL);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $libraries = $attachments['#attached']['library'];
    $this->assertContains('stanford_profile_styles/stanford_profile_styles', $libraries);
    $this->assertContains('stanford_profile_styles/paragraph.react_paragraphs', $libraries);
    $this->assertContains('stanford_profile_styles/layout', $libraries);
    $this->assertCount(3, $libraries);
  }

  /**
   * A node of an unrelated type gets its node.TYPE library attached, with
   * no further special-casing.
   */
  public function testPageAttachmentsOtherNodeType(): void {
    $this->adminContext->method('isAdminRoute')->willReturn(FALSE);

    $node = $this->createMock(NodeInterface::class);
    $node->method('getType')->willReturn('stanford_event');
    $node->expects($this->never())->method('get');

    $this->routeMatch->method('getParameter')->with('node')->willReturn($node);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $libraries = $attachments['#attached']['library'];
    $this->assertContains('stanford_profile_styles/node.stanford_event', $libraries);
    $this->assertNotContains('stanford_profile_styles/node.stanford_page.layout.full-width', $libraries);
  }

  /**
   * A stanford_page node with no layout_selection value gets no full-width
   * library.
   */
  public function testPageAttachmentsStanfordPageNoLayout(): void {
    $this->adminContext->method('isAdminRoute')->willReturn(FALSE);

    $layout_field = $this->createMock(FieldItemListInterface::class);
    $layout_field->method('getValue')->willReturn([]);

    $node = $this->createMock(NodeInterface::class);
    $node->method('getType')->willReturn('stanford_page');
    $node->method('get')->with('layout_selection')->willReturn($layout_field);

    $this->routeMatch->method('getParameter')->willReturn($node);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertNotContains('stanford_profile_styles/node.stanford_page.layout.full-width', $attachments['#attached']['library']);
  }

  /**
   * A stanford_page node with the full-width layout target gets the
   * full-width library attached.
   */
  public function testPageAttachmentsStanfordPageFullWidthLayout(): void {
    $this->adminContext->method('isAdminRoute')->willReturn(FALSE);

    $layout_field = $this->createMock(FieldItemListInterface::class);
    $layout_field->method('getValue')->willReturn([
      ['target_id' => 'stanford_basic_page_full'],
    ]);

    $node = $this->createMock(NodeInterface::class);
    $node->method('getType')->willReturn('stanford_page');
    $node->method('get')->with('layout_selection')->willReturn($layout_field);

    $this->routeMatch->method('getParameter')->willReturn($node);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertContains('stanford_profile_styles/node.stanford_page.layout.full-width', $attachments['#attached']['library']);
  }

  /**
   * A stanford_page node with a different layout target gets no full-width
   * library.
   */
  public function testPageAttachmentsStanfordPageOtherLayout(): void {
    $this->adminContext->method('isAdminRoute')->willReturn(FALSE);

    $layout_field = $this->createMock(FieldItemListInterface::class);
    $layout_field->method('getValue')->willReturn([
      ['target_id' => 'some_other_layout'],
    ]);

    $node = $this->createMock(NodeInterface::class);
    $node->method('getType')->willReturn('stanford_page');
    $node->method('get')->with('layout_selection')->willReturn($layout_field);

    $this->routeMatch->method('getParameter')->willReturn($node);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertNotContains('stanford_profile_styles/node.stanford_page.layout.full-width', $attachments['#attached']['library']);
  }

  /**
   * A stanford_media node gets the media content library attached.
   */
  public function testPageAttachmentsStanfordMedia(): void {
    $this->adminContext->method('isAdminRoute')->willReturn(FALSE);

    $node = $this->createMock(NodeInterface::class);
    $node->method('getType')->willReturn('stanford_media');
    $node->expects($this->never())->method('get');

    $this->routeMatch->method('getParameter')->willReturn($node);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertContains('stanford_profile_styles/node.stanford_media_content', $attachments['#attached']['library']);
  }

  /**
   * The real dist/css directory is scanned and produces the expected
   * library definitions.
   */
  public function testLibraryInfoBuild(): void {
    $this->moduleExtensionList->method('getPath')
      ->with('stanford_profile_styles')
      ->willReturn($this->modulePath);

    $libraries = $this->hooks->libraryInfoBuild();

    $this->assertCount(19, $libraries);

    $this->assertSame(
      ['css' => ['base' => ['dist/css/base/admin/ckeditor.css' => []]]],
      $libraries['admin.ckeditor']
    );
    $this->assertSame(
      ['css' => ['base' => ['dist/css/base/views/search.css' => []]]],
      $libraries['views.search']
    );
    $this->assertSame(
      ['css' => ['component' => ['dist/css/component/node/stanford_page.layout.full-width.css' => []]]],
      $libraries['node.stanford_page.layout.full-width']
    );
    // Files directly inside a `css` subfolder (with no bucket segment) get
    // `next()` returning FALSE for the bucket, so the key is just the lib
    // name.
    $this->assertSame(
      ['css' => ['base' => ['dist/css/base/stanford_profile_styles.css' => []]]],
      $libraries['stanford_profile_styles']
    );
    $this->assertSame(
      ['css' => ['layout' => ['dist/css/layout/layout.css' => []]]],
      $libraries['layout']
    );
  }

  /**
   * No css files present at all — an empty library array is returned.
   */
  public function testLibraryInfoBuildNoFiles(): void {
    $tmp_dir = sys_get_temp_dir() . '/stanford_profile_styles_test_' . uniqid();
    mkdir($tmp_dir . '/dist/css', 0777, TRUE);

    $this->moduleExtensionList->method('getPath')
      ->with('stanford_profile_styles')
      ->willReturn($tmp_dir);

    $libraries = $this->hooks->libraryInfoBuild();

    $this->assertSame([], $libraries);

    rmdir($tmp_dir . '/dist/css');
    rmdir($tmp_dir . '/dist');
    rmdir($tmp_dir);
  }

  /**
   * The react_paragraphs field_formatter library is replaced when present.
   */
  public function testLibraryInfoAlterReactParagraphsWithFieldFormatter(): void {
    $libraries = [
      'field_formatter' => [
        'css' => [
          'component' => [
            'js/build/css/react_paragraphs.field_formatter.css' => [],
          ],
        ],
        'dependencies' => [],
      ],
    ];
    $this->hooks->libraryInfoAlter($libraries, 'react_paragraphs');

    $this->assertArrayNotHasKey('js/build/css/react_paragraphs.field_formatter.css', $libraries['field_formatter']['css']['component']);
    $this->assertContains('stanford_profile_styles/paragraph.react_paragraphs', $libraries['field_formatter']['dependencies']);
  }

  /**
   * The react_paragraphs extension without a field_formatter library key is
   * left untouched.
   */
  public function testLibraryInfoAlterReactParagraphsWithoutFieldFormatter(): void {
    $libraries = ['other' => ['css' => []]];
    $this->hooks->libraryInfoAlter($libraries, 'react_paragraphs');

    $this->assertSame(['other' => ['css' => []]], $libraries);
  }

  /**
   * The confirm_leave library is removed when the CI env var is set.
   */
  public function testLibraryInfoAlterConfirmLeaveWithCi(): void {
    putenv('CI=1');

    $libraries = ['confirm-leave' => ['js' => []]];
    $this->hooks->libraryInfoAlter($libraries, 'confirm_leave');

    $this->assertArrayNotHasKey('confirm-leave', $libraries);
  }

  /**
   * The confirm_leave library is left alone when CI is not set.
   */
  public function testLibraryInfoAlterConfirmLeaveWithoutCi(): void {
    putenv('CI');

    $libraries = ['confirm-leave' => ['js' => []]];
    $this->hooks->libraryInfoAlter($libraries, 'confirm_leave');

    $this->assertArrayHasKey('confirm-leave', $libraries);
  }

  /**
   * An unrelated extension is left completely untouched.
   */
  public function testLibraryInfoAlterOtherExtension(): void {
    $libraries = ['something' => ['css' => []]];
    $this->hooks->libraryInfoAlter($libraries, 'some_other_module');

    $this->assertSame(['something' => ['css' => []]], $libraries);
  }

  /**
   * A non-admin node entity display gets the node.BUNDLE library attached.
   */
  public function testEntityDisplayBuildAlterNode(): void {
    $this->adminContext->method('isAdminRoute')->willReturn(FALSE);

    $entity = $this->createMock(NodeInterface::class);
    $entity->method('getEntityTypeId')->willReturn('node');
    $entity->method('bundle')->willReturn('stanford_page');

    $build = [];
    $this->hooks->entityDisplayBuildAlter($build, ['entity' => $entity]);

    $this->assertContains('stanford_profile_styles/node.stanford_page', $build['#attached']['library']);
  }

  /**
   * An admin route node entity display gets no library attached.
   */
  public function testEntityDisplayBuildAlterAdminNode(): void {
    $this->adminContext->method('isAdminRoute')->willReturn(TRUE);

    $entity = $this->createMock(NodeInterface::class);
    $entity->method('getEntityTypeId')->willReturn('node');
    $entity->expects($this->never())->method('bundle');

    $build = [];
    $this->hooks->entityDisplayBuildAlter($build, ['entity' => $entity]);

    $this->assertArrayNotHasKey('#attached', $build);
  }

  /**
   * A paragraph entity display gets the paragraph.BUNDLE library attached,
   * with the `stanford_` prefix stripped from the bundle name.
   */
  public function testEntityDisplayBuildAlterParagraph(): void {
    $this->adminContext->method('isAdminRoute')->willReturn(FALSE);

    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('paragraph');
    $entity->method('bundle')->willReturn('stanford_spacer');

    $build = [];
    $this->hooks->entityDisplayBuildAlter($build, ['entity' => $entity]);

    $this->assertContains('stanford_profile_styles/paragraph.spacer', $build['#attached']['library']);
  }

  /**
   * An entity type that is neither node nor paragraph gets no library
   * attached.
   */
  public function testEntityDisplayBuildAlterOtherEntityType(): void {
    $this->adminContext->method('isAdminRoute')->willReturn(FALSE);

    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('taxonomy_term');
    $entity->expects($this->never())->method('bundle');

    $build = [];
    $this->hooks->entityDisplayBuildAlter($build, ['entity' => $entity]);

    $this->assertArrayNotHasKey('#attached', $build);
  }

  /**
   * No associated filter format — nothing is added to the css array.
   */
  public function testCkeditorCssAlterNoFilterFormat(): void {
    $editor = $this->createMock(Editor::class);
    $editor->method('hasAssociatedFilterFormat')->willReturn(FALSE);
    $editor->expects($this->never())->method('getFilterFormat');
    $this->moduleExtensionList->expects($this->never())->method('getPath');

    $css = [];
    $this->hooks->ckeditorCssAlter($css, $editor);

    $this->assertSame([], $css);
  }

  /**
   * A filter format that is not one of the known formats gets no css
   * added.
   */
  public function testCkeditorCssAlterUnknownFormat(): void {
    $filter_format = $this->createMock(FilterFormatInterface::class);
    $filter_format->method('id')->willReturn('basic_html');

    $editor = $this->createMock(Editor::class);
    $editor->method('hasAssociatedFilterFormat')->willReturn(TRUE);
    $editor->method('getFilterFormat')->willReturn($filter_format);
    $this->moduleExtensionList->expects($this->never())->method('getPath');

    $css = [];
    $this->hooks->ckeditorCssAlter($css, $editor);

    $this->assertSame([], $css);
  }

  /**
   * A known stanford_html filter format gets the ckeditor css appended.
   */
  public function testCkeditorCssAlterKnownFormat(): void {
    $filter_format = $this->createMock(FilterFormatInterface::class);
    $filter_format->method('id')->willReturn('stanford_html');

    $editor = $this->createMock(Editor::class);
    $editor->method('hasAssociatedFilterFormat')->willReturn(TRUE);
    $editor->method('getFilterFormat')->willReturn($filter_format);

    $this->moduleExtensionList->method('getPath')
      ->with('stanford_profile_styles')
      ->willReturn('/modules/stanford_profile_styles');

    $css = [];
    $this->hooks->ckeditorCssAlter($css, $editor);

    $this->assertContains('/modules/stanford_profile_styles/dist/css/base/admin/ckeditor.css', $css);
  }

  /**
   * The other known stanford_minimal_html filter format also gets the
   * ckeditor css appended.
   */
  public function testCkeditorCssAlterKnownMinimalFormat(): void {
    $filter_format = $this->createMock(FilterFormatInterface::class);
    $filter_format->method('id')->willReturn('stanford_minimal_html');

    $editor = $this->createMock(Editor::class);
    $editor->method('hasAssociatedFilterFormat')->willReturn(TRUE);
    $editor->method('getFilterFormat')->willReturn($filter_format);

    $this->moduleExtensionList->method('getPath')
      ->with('stanford_profile_styles')
      ->willReturn('/modules/stanford_profile_styles');

    $css = [];
    $this->hooks->ckeditorCssAlter($css, $editor);

    $this->assertContains('/modules/stanford_profile_styles/dist/css/base/admin/ckeditor.css', $css);
  }

}
