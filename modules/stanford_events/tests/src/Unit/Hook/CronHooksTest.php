<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events\Unit\Hook;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\stanford_events\Hook\CronHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for CronHooks.
 */
#[Group('stanford_events')]
#[CoversClass(CronHooks::class)]
class CronHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_events\Hook\CronHooks
   */
  protected CronHooks $hooks;

  /**
   * The mocked entity query.
   *
   * @var \Drupal\Core\Entity\Query\QueryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $query;

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

    $this->query = $this->createMock(QueryInterface::class);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('getQuery')->willReturn($this->query);

    $entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $entityTypeManager->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $state = $this->createMock(StateInterface::class);
    $state->method('get')
      ->with('system.cron_last')
      ->willReturn(1000);

    $container = new ContainerBuilder();
    $this->cacheTagsInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $container->set('cache_tags.invalidator', $this->cacheTagsInvalidator);
    \Drupal::setContainer($container);

    $this->hooks = new CronHooks($entityTypeManager, $state);
  }

  /**
   * No expired events found — cache should not be invalidated.
   */
  public function testCronNoExpiredEvents(): void {
    $this->query->expects($this->once())->method('accessCheck')->with(FALSE);
    $this->query->expects($this->exactly(4))->method('condition');
    $this->query->expects($this->once())->method('execute')->willReturn([]);

    $this->cacheTagsInvalidator->expects($this->never())->method('invalidateTags');

    $this->hooks->cron();
  }

  /**
   * Expired events found — cache tags for node list and each node cleared.
   */
  public function testCronWithExpiredEvents(): void {
    $this->query->expects($this->once())->method('accessCheck')->with(FALSE);
    $this->query->expects($this->exactly(4))->method('condition');
    $this->query->expects($this->once())
      ->method('execute')
      ->willReturn([5 => '5', 7 => '7']);

    $this->cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['node_list:stanford_event', 'node:5', 'node:7']);

    $this->hooks->cron();
  }

}
