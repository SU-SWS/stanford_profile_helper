<?php

declare(strict_types=1);

namespace Drupal\Tests\jumpstart_ui\Unit\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\jumpstart_ui\Hook\LayoutHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for LayoutHooks.
 */
#[Group('jumpstart_ui')]
#[CoversClass(LayoutHooks::class)]
class LayoutHooksTest extends UnitTestCase {

  /**
   * Builds the hook object with a mocked route match returning $route_name.
   */
  protected function buildHooks(string $route_name): LayoutHooks {
    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getRouteName')->willReturn($route_name);
    return new LayoutHooks($routeMatch);
  }

  /**
   * When on a layout builder route, the admin flag should be set.
   */
  public function testPreprocessLayoutOnLayoutBuilderRoute(): void {
    $hooks = $this->buildHooks('layout_builder.overrides.node.view');
    $variables = [];
    $hooks->preprocessLayout($variables);
    $this->assertArrayHasKey('layout_builder_admin', $variables);
    $this->assertTrue($variables['layout_builder_admin']);
  }

  /**
   * When not on a layout builder route, the flag should not be set.
   */
  public function testPreprocessLayoutOnNonLayoutBuilderRoute(): void {
    $hooks = $this->buildHooks('entity.node.canonical');
    $variables = [];
    $hooks->preprocessLayout($variables);
    $this->assertArrayNotHasKey('layout_builder_admin', $variables);
  }

  /**
   * An empty route name should also not match the layout builder prefix.
   */
  public function testPreprocessLayoutWithEmptyRouteName(): void {
    $hooks = $this->buildHooks('');
    $variables = [];
    $hooks->preprocessLayout($variables);
    $this->assertArrayNotHasKey('layout_builder_admin', $variables);
  }

}
