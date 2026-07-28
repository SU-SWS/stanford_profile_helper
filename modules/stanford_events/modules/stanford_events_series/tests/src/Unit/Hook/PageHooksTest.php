<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events_series\Unit\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_events_series\Hook\PageHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for PageHooks.
 */
#[Group('stanford_events_series')]
#[CoversClass(PageHooks::class)]
class PageHooksTest extends UnitTestCase {

  /**
   * The mocked route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $routeMatch;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
  }

  /**
   * Builds the hook object with the mocked route match.
   */
  protected function buildHooks(): PageHooks {
    return new PageHooks($this->routeMatch);
  }

  /**
   * When there is no node on the route, nothing is attached.
   */
  public function testPageAttachmentsNoNode(): void {
    $this->routeMatch->method('getParameter')->with('node')->willReturn(NULL);

    $attachments = [];
    $this->buildHooks()->pageAttachments($attachments);

    $this->assertArrayNotHasKey('#attached', $attachments);
  }

  /**
   * When the node on the route is not a series node, nothing is attached.
   */
  public function testPageAttachmentsOtherBundle(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('stanford_event');
    $this->routeMatch->method('getParameter')->with('node')->willReturn($node);

    $attachments = [];
    $this->buildHooks()->pageAttachments($attachments);

    $this->assertArrayNotHasKey('#attached', $attachments);
  }

  /**
   * When the node on the route is a series node, the library is attached.
   */
  public function testPageAttachmentsEventSeriesBundle(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('stanford_event_series');
    $this->routeMatch->method('getParameter')->with('node')->willReturn($node);

    $attachments = [];
    $this->buildHooks()->pageAttachments($attachments);

    $this->assertSame(
      ['stanford_events_series/event_series_node'],
      $attachments['#attached']['library']
    );
  }

}
