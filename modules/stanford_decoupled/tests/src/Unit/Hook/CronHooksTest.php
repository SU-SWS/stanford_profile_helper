<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_decoupled\Unit\Hook;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\ConditionInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\next\Event\EntityActionEvent;
use Drupal\next\EventSubscriber\EntityActionEventDispatcher;
use Drupal\next\NextEntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_decoupled\Hook\CronHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

// The `next` module's procedural next_entity_update() is not autoloaded via
// PSR-4 (it lives in a .module file), so it must be required manually before
// CronHooks::cron() can call it.
if (!function_exists('next_entity_update')) {
  require_once DRUPAL_ROOT . '/modules/contrib/next/next.module';
}

/**
 * Unit tests for CronHooks.
 */
#[Group('stanford_decoupled')]
#[CoversClass(CronHooks::class)]
class CronHooksTest extends UnitTestCase {

  /**
   * The state service mock.
   *
   * @var \Drupal\Core\State\StateInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected StateInterface $state;

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The cache backend mock used by DecoupledConfigOverrides::isDecoupled().
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected CacheBackendInterface $cache;

  /**
   * Real dispatcher (final class) wired with a mocked event dispatcher so
   * next_entity_update() -> EntityActionEvent::createFromEntity() ->
   * \Drupal::service('next.entity_action_event_dispatcher') has something
   * real to call.
   *
   * @var \Drupal\next\EventSubscriber\EntityActionEventDispatcher
   */
  protected EntityActionEventDispatcher $dispatcher;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->state = $this->createMock(StateInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);

    $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    $this->dispatcher = new EntityActionEventDispatcher($eventDispatcher);

    $nextEntityTypeManager = $this->createMock(NextEntityTypeManagerInterface::class);
    $nextEntityTypeManager->method('getSitesForEntity')->willReturn([]);

    $container = new ContainerBuilder();
    $container->set('cache.default', $this->cache);
    $container->set('next.entity_action_event_dispatcher', $this->dispatcher);
    $container->set('next.entity_type.manager', $nextEntityTypeManager);
    \Drupal::setContainer($container);
  }

  /**
   * When the site is not decoupled, cron should return early.
   */
  public function testCronNotDecoupled(): void {
    $this->cache->method('get')
      ->with('stanford_decoupled')
      ->willReturn((object) ['data' => FALSE]);

    $this->state->expects($this->never())->method('get');
    $this->state->expects($this->never())->method('set');
    $this->entityTypeManager->expects($this->never())->method('getStorage');

    $hooks = new CronHooks($this->state, $this->entityTypeManager);
    $hooks->cron();
  }

  /**
   * Decoupled site, no matching nodes found by the query.
   */
  public function testCronDecoupledNoResults(): void {
    $this->cache->method('get')
      ->with('stanford_decoupled')
      ->willReturn((object) ['data' => TRUE]);

    $this->state->method('get')
      ->with('stanford-decoupled-last-ran', 0)
      ->willReturn(1000);
    $this->state->expects($this->once())
      ->method('set')
      ->with('stanford-decoupled-last-ran', $this->isType('int'));

    $storage = $this->mockNodeStorage([]);
    $storage->expects($this->once())->method('loadMultiple')->with([])->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $hooks = new CronHooks($this->state, $this->entityTypeManager);
    $hooks->cron();
  }

  /**
   * Decoupled site with nodes found triggers next_entity_update() for each.
   */
  public function testCronDecoupledWithResults(): void {
    $this->cache->method('get')
      ->with('stanford_decoupled')
      ->willReturn((object) ['data' => TRUE]);

    $this->state->method('get')->willReturn(500);
    $this->state->expects($this->once())->method('set');

    $storage = $this->mockNodeStorage([1, 2]);

    $node1 = $this->createMock(NodeInterface::class);
    $node1->method('hasLinkTemplate')->willReturn(FALSE);
    $node2 = $this->createMock(NodeInterface::class);
    $node2->method('hasLinkTemplate')->willReturn(FALSE);

    $storage->expects($this->once())
      ->method('loadMultiple')
      ->with([1, 2])
      ->willReturn([$node1, $node2]);

    $this->entityTypeManager->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $hooks = new CronHooks($this->state, $this->entityTypeManager);
    $hooks->cron();

    // Verify next_entity_update() queued an event for each loaded node by
    // inspecting the dispatcher's private $events queue.
    $reflection = new \ReflectionProperty(EntityActionEventDispatcher::class, 'events');
    $reflection->setAccessible(TRUE);
    $events = $reflection->getValue($this->dispatcher);

    $this->assertCount(2, $events);
    foreach ($events as $event) {
      $this->assertInstanceOf(EntityActionEvent::class, $event);
      $this->assertSame('update', $event->getAction());
    }
  }

  /**
   * Builds a fully chained node storage/query mock.
   *
   * @param array $executeResult
   *   The result of ->execute() on the built query.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface&\PHPUnit\Framework\MockObject\MockObject
   *   The storage mock (with getQuery() wired to return a chainable query).
   */
  protected function mockNodeStorage(array $executeResult): EntityStorageInterface {
    $conditionGroup = $this->createMock(ConditionInterface::class);
    $conditionGroup->method('condition')->willReturnSelf();

    $query = $this->createMock(QueryInterface::class);
    $query->method('accessCheck')->willReturnSelf();
    $query->method('condition')->willReturnSelf();
    $query->method('orConditionGroup')->willReturn($conditionGroup);
    $query->method('andConditionGroup')->willReturn($conditionGroup);
    $query->expects($this->once())->method('execute')->willReturn($executeResult);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($query);

    return $storage;
  }

}
