<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_styles\Unit\Hook;

use Drupal\stanford_profile_styles\Hook\ViewsHooks;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for ViewsHooks.
 */
#[Group('stanford_profile_styles')]
#[CoversClass(ViewsHooks::class)]
class ViewsHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_styles\Hook\ViewsHooks
   */
  protected ViewsHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new ViewsHooks();
  }

  /**
   * Builds a mocked ViewExecutable with the given view/storage id.
   *
   * Note that ViewExecutable::id() simply proxies to storage->id(), so a
   * single id value drives both the `$view->id()` and
   * `$view->storage->id()` checks in the hook under test.
   *
   * @param string $id
   *   The value both ViewExecutable::id() and storage->id() resolve to.
   *
   * @return \Drupal\views\ViewExecutable
   *   A mocked view executable.
   */
  protected function buildView(string $id): ViewExecutable {
    $storage = $this->createMock(ViewEntityInterface::class);
    $storage->method('id')->willReturn($id);

    /** @var \Drupal\views\ViewExecutable $view */
    $view = $this->getMockBuilder(ViewExecutable::class)
      ->disableOriginalConstructor()
      ->onlyMethods([])
      ->getMock();
    $view->storage = $storage;

    return $view;
  }

  /**
   * The media_content view gets the dialog ajax library attached.
   */
  public function testViewsPreRenderMediaContent(): void {
    $view = $this->buildView('media_content');
    $this->hooks->viewsPreRender($view);

    $this->assertContains('core/drupal.dialog.ajax', $view->element['#attached']['library']);
  }

  /**
   * The search view gets the views.search library attached.
   */
  public function testViewsPreRenderSearch(): void {
    $view = $this->buildView('search');
    $this->hooks->viewsPreRender($view);

    $this->assertContains('stanford_profile_styles/views.search', $view->element['#attached']['library']);
  }

  /**
   * The taxonomy_term_pages view (matched via storage id) gets the
   * stanford_person views library attached.
   */
  public function testViewsPreRenderTaxonomyTermPages(): void {
    $view = $this->buildView('taxonomy_term_pages');
    $this->hooks->viewsPreRender($view);

    $this->assertContains('stanford_person/views', $view->element['#attached']['library']);
  }

  /**
   * An unrelated view gets no extra libraries attached — only the default
   * views/views.module library that ViewExecutable ships with remains.
   */
  public function testViewsPreRenderUnrelatedView(): void {
    $view = $this->buildView('unrelated_view');
    $this->hooks->viewsPreRender($view);

    $this->assertSame(['views/views.module'], $view->element['#attached']['library']);
  }

}
