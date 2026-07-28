<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_courses\Unit\Hook;

use Drupal\stanford_courses\Hook\ViewsHooks;
use Drupal\Tests\UnitTestCase;
use Drupal\views\ViewEntityInterface;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for ViewsHooks.
 */
#[Group('stanford_courses')]
#[CoversClass(ViewsHooks::class)]
class ViewsHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_courses\Hook\ViewsHooks
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
   * Builds a mocked ViewExecutable with the given view id.
   */
  protected function createViewMock(string $id): ViewExecutable {
    $storage = $this->createMock(ViewEntityInterface::class);
    $storage->method('id')->willReturn($id);

    $view = $this->createMock(ViewExecutable::class);
    $view->storage = $storage;
    $view->element = [];

    return $view;
  }

  /**
   * A view other than 'stanford_courses' should not get the library attached.
   */
  public function testViewsPreRenderOtherView() {
    $view = $this->createViewMock('some_other_view');
    $this->hooks->viewsPreRender($view);
    $this->assertArrayNotHasKey('#attached', $view->element);
  }

  /**
   * The 'stanford_courses' view should get the library attached.
   */
  public function testViewsPreRenderStanfordCoursesView() {
    $view = $this->createViewMock('stanford_courses');
    $this->hooks->viewsPreRender($view);
    $this->assertSame(
      ['stanford_courses/courses_page'],
      $view->element['#attached']['library']
    );
  }

}
