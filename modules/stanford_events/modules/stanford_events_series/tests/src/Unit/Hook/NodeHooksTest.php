<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events_series\Unit\Hook;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\node\NodeInterface;
use Drupal\stanford_events_series\Hook\NodeHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for NodeHooks.
 */
#[Group('stanford_events_series')]
#[CoversClass(NodeHooks::class)]
class NodeHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_events_series\Hook\NodeHooks
   */
  protected NodeHooks $hooks;

  /**
   * The mocked cache tags invalidator.
   *
   * @var \Drupal\Core\Cache\CacheTagsInvalidatorInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheTagsInvalidator;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new NodeHooks();

    $container = new ContainerBuilder();
    $this->cacheTagsInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);
    \Drupal::setContainer($container);
  }

  /**
   * A node of a different bundle should not trigger a cache invalidation.
   */
  public function testNodePresaveOtherBundle(): void {
    $entity = $this->createMock(NodeInterface::class);
    $entity->method('bundle')->willReturn('stanford_event');

    $this->cacheTagsInvalidator->expects($this->never())->method('invalidateTags');

    $this->hooks->nodePresave($entity);
  }

  /**
   * A stanford_event_series node clears its node list cache tag.
   */
  public function testNodePresaveEventSeriesBundle(): void {
    $entity = $this->createMock(NodeInterface::class);
    $entity->method('bundle')->willReturn('stanford_event_series');

    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['node_list:stanford_event_series']);

    $this->hooks->nodePresave($entity);
  }

}
