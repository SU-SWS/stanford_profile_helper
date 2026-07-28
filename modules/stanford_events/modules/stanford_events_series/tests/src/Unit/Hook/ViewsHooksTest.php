<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events_series\Unit\Hook;

use Drupal\stanford_events_series\Hook\ViewsHooks;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for ViewsHooks.
 */
#[Group('stanford_events_series')]
#[CoversClass(ViewsHooks::class)]
class ViewsHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_events_series\Hook\ViewsHooks
   */
  protected ViewsHooks $hooks;

  /**
   * The mocked cache plugin, unused by the hook but required by signature.
   *
   * @var \Drupal\views\Plugin\views\cache\CachePluginBase|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new ViewsHooks();
    $this->cache = $this->createMock(CachePluginBase::class);
  }

  /**
   * Builds a mocked ViewExecutable with the given id and type filter value.
   */
  protected function createViewMock(string $id, array $typeValue = []): ViewExecutable {
    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn($id);

    $typeFilter = new \stdClass();
    $typeFilter->value = $typeValue;

    $view->filter = [
      'type' => $typeFilter,
    ];

    return $view;
  }

  /**
   * A view on the allow list gets the library attached and its cache tags
   * rewritten to be node-type specific.
   */
  public function testViewsPostRenderAllowList(): void {
    $view = $this->createViewMock('stanford_event_series', ['stanford_event_series']);
    $output = [
      '#cache' => [
        'tags' => ['some_tag', 'node_list', 'other_tag'],
      ],
    ];

    $this->hooks->viewsPostRender($view, $output, $this->cache);

    $this->assertSame(['stanford_events_series/event_series_views'], $output['#attached']['library']);
    $this->assertNotContains('node_list', $output['#cache']['tags']);
    $this->assertContains('node_list:stanford_event_series', $output['#cache']['tags']);
  }

  /**
   * A view not on the allow list is left completely untouched.
   */
  public function testViewsPostRenderNotOnAllowList(): void {
    $view = $this->createViewMock('some_unrelated_view');
    $output = [
      '#cache' => [
        'tags' => ['node_list'],
      ],
    ];

    $this->hooks->viewsPostRender($view, $output, $this->cache);

    $this->assertArrayNotHasKey('#attached', $output);
    $this->assertSame(['node_list'], $output['#cache']['tags']);
  }

}
