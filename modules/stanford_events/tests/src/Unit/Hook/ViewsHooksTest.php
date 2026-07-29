<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events\Unit\Hook;

use Drupal\stanford_events\Hook\ViewsHooks;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for ViewsHooks.
 */
#[Group('stanford_events')]
#[CoversClass(ViewsHooks::class)]
class ViewsHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_events\Hook\ViewsHooks
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
   * Builds a mocked ViewExecutable with the given id and filter values.
   */
  protected function createViewMock(string $id, array $typeValue = [], array $vidValue = []): ViewExecutable {
    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn($id);

    $typeFilter = new \stdClass();
    $typeFilter->value = $typeValue;

    $vidFilter = new \stdClass();
    $vidFilter->value = $vidValue;

    $view->filter = [
      'type' => $typeFilter,
      'vid' => $vidFilter,
    ];

    return $view;
  }

  /**
   * A node base table view on the allow list gets the library attached and
   * its cache tags rewritten to be node-type specific.
   */
  public function testViewsPostRenderNodeAllowList(): void {
    $view = $this->createViewMock('stanford_events', ['stanford_event']);
    $output = [
      '#cache' => [
        'tags' => ['some_tag', 'node_list', 'other_tag'],
      ],
    ];

    $this->hooks->viewsPostRender($view, $output, $this->cache);

    $this->assertSame(['stanford_events/event_views'], $output['#attached']['library']);
    $this->assertNotContains('node_list', $output['#cache']['tags']);
    $this->assertContains('node_list:stanford_event', $output['#cache']['tags']);
  }

  /**
   * Every id in the node allow list gets rewritten cache tags.
   */
  public function testViewsPostRenderAllNodeAllowListIds(): void {
    foreach (['stanford_events', 'stanford_events_past', 'stanford_events_schedule'] as $id) {
      $view = $this->createViewMock($id, ['stanford_event', 'stanford_event_series']);
      $output = [
        '#cache' => [
          'tags' => ['node_list'],
        ],
      ];

      $this->hooks->viewsPostRender($view, $output, $this->cache);

      $this->assertSame(['stanford_events/event_views'], $output['#attached']['library']);
      $this->assertContains('node_list:stanford_event', $output['#cache']['tags']);
      $this->assertContains('node_list:stanford_event_series', $output['#cache']['tags']);
    }
  }

  /**
   * A term base table view on the allow list gets the library attached and
   * its cache tags rewritten to be term-vocabulary specific.
   */
  public function testViewsPostRenderTermAllowList(): void {
    $view = $this->createViewMock('stanford_event_terms_utility', [], ['event_types']);
    $output = [
      '#cache' => [
        'tags' => ['term_list'],
      ],
    ];

    $this->hooks->viewsPostRender($view, $output, $this->cache);

    $this->assertSame(['stanford_events/event_views'], $output['#attached']['library']);
    $this->assertNotContains('term_list', $output['#cache']['tags']);
    $this->assertContains('term_list:event_types', $output['#cache']['tags']);
  }

  /**
   * A view not on either allow list is left completely untouched.
   */
  public function testViewsPostRenderNotOnAnyAllowList(): void {
    $view = $this->createViewMock('some_unrelated_view');
    $output = [
      '#cache' => [
        'tags' => ['node_list', 'term_list'],
      ],
    ];

    $this->hooks->viewsPostRender($view, $output, $this->cache);

    $this->assertArrayNotHasKey('#attached', $output);
    $this->assertSame(['node_list', 'term_list'], $output['#cache']['tags']);
  }

}
