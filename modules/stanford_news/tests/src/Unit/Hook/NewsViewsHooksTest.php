<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_news\Unit\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\stanford_news\Hook\NewsViewsHooks;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for NewsViewsHooks.
 */
#[Group('stanford_news')]
#[CoversClass(NewsViewsHooks::class)]
class NewsViewsHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_news\Hook\NewsViewsHooks
   */
  protected NewsViewsHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new NewsViewsHooks();
  }

  /**
   * Builds a mocked ViewExecutable with the given storage ID and filter
   * type values.
   */
  protected function createView(string $storageId, array $filterTypeValues = []): ViewExecutable {
    $storage = $this->createMock(EntityInterface::class);
    $storage->method('id')->willReturn($storageId);

    $view = $this->createMock(ViewExecutable::class);
    $view->storage = $storage;
    $view->filter = ['type' => (object) ['value' => $filterTypeValues]];

    return $view;
  }

  /**
   * Views other than stanford_news are left completely untouched.
   */
  public function testViewsPostRenderIgnoresOtherViews(): void {
    $view = $this->createView('other_view');
    $cache = $this->createMock(CachePluginBase::class);
    $output = [
      '#attached' => [],
      '#cache' => ['tags' => ['node_list', 'some_other_tag']],
    ];

    $this->hooks->viewsPostRender($view, $output, $cache);

    $this->assertSame([], $output['#attached']);
    $this->assertSame(['node_list', 'some_other_tag'], $output['#cache']['tags']);
  }

  /**
   * The stanford_news view gets its library attached and the node_list
   * cache tag replaced with per-type cache tags.
   */
  public function testViewsPostRenderProcessesStanfordNewsView(): void {
    $view = $this->createView('stanford_news', ['article', 'press_release']);
    $cache = $this->createMock(CachePluginBase::class);
    $output = [
      '#attached' => [],
      '#cache' => ['tags' => ['node_list', 'some_other_tag']],
    ];

    $this->hooks->viewsPostRender($view, $output, $cache);

    $this->assertSame(['stanford_news/news_list'], $output['#attached']['library']);
    $this->assertNotContains('node_list', $output['#cache']['tags']);
    $this->assertContains('some_other_tag', $output['#cache']['tags']);
    $this->assertContains('node_list:article', $output['#cache']['tags']);
    $this->assertContains('node_list:press_release', $output['#cache']['tags']);
  }

  /**
   * A stanford_news view with no configured node types still attaches the
   * library and strips the node_list tag, adding no per-type tags.
   */
  public function testViewsPostRenderProcessesStanfordNewsViewWithNoTypes(): void {
    $view = $this->createView('stanford_news', []);
    $cache = $this->createMock(CachePluginBase::class);
    $output = [
      '#attached' => [],
      '#cache' => ['tags' => ['node_list']],
    ];

    $this->hooks->viewsPostRender($view, $output, $cache);

    $this->assertSame(['stanford_news/news_list'], $output['#attached']['library']);
    $this->assertNotContains('node_list', $output['#cache']['tags']);
  }

}
