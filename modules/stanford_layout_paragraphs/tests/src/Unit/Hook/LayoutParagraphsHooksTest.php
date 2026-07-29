<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_layout_paragraphs\Unit\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\stanford_layout_paragraphs\Hook\LayoutParagraphsHooks;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit tests for LayoutParagraphsHooks.
 */
#[Group('stanford_layout_paragraphs')]
#[CoversClass(LayoutParagraphsHooks::class)]
class LayoutParagraphsHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_layout_paragraphs\Hook\LayoutParagraphsHooks
   */
  protected LayoutParagraphsHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new LayoutParagraphsHooks();
  }

  /**
   * Puts a route match returning the given route name into the container.
   */
  protected function setCurrentRoute(?string $routeName): void {
    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getRouteName')->willReturn($routeName);

    $container = new ContainerBuilder();
    $container->set('current_route_match', $routeMatch);
    \Drupal::setContainer($container);
  }

  /**
   * Invokes the protected isEditingLayoutParagraphs() method.
   */
  protected function invokeIsEditing(): bool {
    $method = new \ReflectionMethod($this->hooks, 'isEditingLayoutParagraphs');
    return $method->invoke($this->hooks);
  }

  /**
   * Builds a mocked ViewExecutable with a mocked query plugin attached.
   */
  protected function createViewExecutable(string $viewId, int $limit): ViewExecutable&MockObject {
    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn($viewId);

    $query = $this->createMock(QueryPluginBase::class);
    $query->method('getLimit')->willReturn($limit);
    $view->query = $query;

    return $view;
  }

  /**
   * The dependency is added when the extension matches.
   */
  public function testLibraryInfoAlterAddsDependencyForMatchingExtension(): void {
    $libraries = ['builder' => ['dependencies' => []]];
    $this->hooks->libraryInfoAlter($libraries, 'layout_paragraphs');
    $this->assertContains('stanford_layout_paragraphs/layout_paragraphs', $libraries['builder']['dependencies']);
  }

  /**
   * Other extensions are left untouched.
   */
  public function testLibraryInfoAlterIgnoresOtherExtensions(): void {
    $libraries = ['builder' => ['dependencies' => []]];
    $this->hooks->libraryInfoAlter($libraries, 'some_other_extension');
    $this->assertSame(['builder' => ['dependencies' => []]], $libraries);
  }

  /**
   * The behavior plugin class is always overridden.
   */
  public function testParagraphsBehaviorInfoAlter(): void {
    $paragraphs_behavior = [];
    $this->hooks->paragraphsBehaviorInfoAlter($paragraphs_behavior);
    $this->assertSame(
      'Drupal\stanford_layout_paragraphs\Plugin\paragraphs\Behavior\LayoutParagraphs',
      $paragraphs_behavior['layout_paragraphs']['class']
    );
  }

  /**
   * A null route name means we're not editing layout paragraphs.
   */
  public function testIsEditingLayoutParagraphsNullRoute(): void {
    $this->setCurrentRoute(NULL);
    $this->assertFalse($this->invokeIsEditing());
  }

  /**
   * An unrelated route name is not considered an editing route.
   */
  public function testIsEditingLayoutParagraphsUnrelatedRoute(): void {
    $this->setCurrentRoute('some.unrelated.route');
    $this->assertFalse($this->invokeIsEditing());
  }

  /**
   * A route explicitly listed is considered an editing route.
   */
  public function testIsEditingLayoutParagraphsKnownRoute(): void {
    $this->setCurrentRoute('entity.node.edit_form');
    $this->assertTrue($this->invokeIsEditing());
  }

  /**
   * Any route prefixed with layout_paragraphs. is an editing route.
   */
  public function testIsEditingLayoutParagraphsPrefixedRoute(): void {
    $this->setCurrentRoute('layout_paragraphs.builder');
    $this->assertTrue($this->invokeIsEditing());
  }

  /**
   * Nothing changes when not on a layout paragraphs editing route.
   */
  public function testPreprocessNotEditingLayoutParagraphs(): void {
    $this->setCurrentRoute(NULL);
    $variables = [
      'elements' => ['#entity_type' => 'node'],
      'title_suffix' => ['contextual_links' => ['foo' => 'bar']],
    ];
    $this->hooks->preprocess($variables, 'block');
    $this->assertArrayHasKey('contextual_links', $variables['title_suffix']);
  }

  /**
   * Contextual links are removed when editing and #entity_type is set.
   */
  public function testPreprocessEditingWithEntityType(): void {
    $this->setCurrentRoute('entity.node.edit_form');
    $variables = [
      'elements' => ['#entity_type' => 'node'],
      'title_suffix' => ['contextual_links' => ['foo' => 'bar']],
    ];
    $this->hooks->preprocess($variables, 'block');
    $this->assertArrayNotHasKey('contextual_links', $variables['title_suffix']);
  }

  /**
   * Contextual links are left alone when there is no #entity_type.
   */
  public function testPreprocessEditingWithoutEntityType(): void {
    $this->setCurrentRoute('entity.node.edit_form');
    $variables = [
      'elements' => [],
      'title_suffix' => ['contextual_links' => ['foo' => 'bar']],
    ];
    $this->hooks->preprocess($variables, 'block');
    $this->assertArrayHasKey('contextual_links', $variables['title_suffix']);
  }

  /**
   * Nothing happens when not editing layout paragraphs.
   */
  public function testViewsPreExecuteNotEditingLayoutParagraphs(): void {
    $this->setCurrentRoute(NULL);
    $view = $this->createViewExecutable('some_view', 0);
    $view->query->expects($this->never())->method('setLimit');
    $this->hooks->viewsPreExecute($view);
  }

  /**
   * Excluded views (media_library) are never limited.
   */
  public function testViewsPreExecuteExcludedView(): void {
    $this->setCurrentRoute('entity.node.edit_form');
    $view = $this->createViewExecutable('media_library', 0);
    $view->query->expects($this->never())->method('setLimit');
    $this->hooks->viewsPreExecute($view);
  }

  /**
   * A limit of zero (or less) is bumped up to 6.
   */
  public function testViewsPreExecuteSetsLimitWhenZeroOrLess(): void {
    $this->setCurrentRoute('entity.node.edit_form');
    $view = $this->createViewExecutable('some_view', 0);
    $view->query->expects($this->once())->method('setLimit')->with(6);
    $this->hooks->viewsPreExecute($view);
  }

  /**
   * A limit above 5 is capped down to 6.
   */
  public function testViewsPreExecuteSetsLimitWhenAboveFive(): void {
    $this->setCurrentRoute('entity.node.edit_form');
    $view = $this->createViewExecutable('some_view', 10);
    $view->query->expects($this->once())->method('setLimit')->with(6);
    $this->hooks->viewsPreExecute($view);
  }

  /**
   * A limit already within range 1-5 is left untouched.
   */
  public function testViewsPreExecuteDoesNotChangeLimitWithinRange(): void {
    $this->setCurrentRoute('entity.node.edit_form');
    $view = $this->createViewExecutable('some_view', 3);
    $view->query->expects($this->never())->method('setLimit');
    $this->hooks->viewsPreExecute($view);
  }

}
