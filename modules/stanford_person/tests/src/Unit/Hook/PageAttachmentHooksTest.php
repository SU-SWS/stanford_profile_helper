<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_person\Unit\Hook;

use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_person\Hook\PageAttachmentHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for PageAttachmentHooks.
 */
#[Group('stanford_person')]
#[CoversClass(PageAttachmentHooks::class)]
class PageAttachmentHooksTest extends UnitTestCase {

  /**
   * The route match mock.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Routing\RouteMatchInterface
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
   * No node parameter on the route — nothing should be attached.
   */
  public function testNoNodeParameter() {
    $this->routeMatch->method('getParameter')
      ->with('node')
      ->willReturn(NULL);

    $hooks = new PageAttachmentHooks($this->routeMatch);
    $attachments = [];
    $hooks->pageAttachments($attachments);
    $this->assertArrayNotHasKey('#attached', $attachments);
  }

  /**
   * Node parameter exists but is a different bundle — no library attached.
   */
  public function testNodeWrongBundle() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('getType')->willReturn('article');

    $this->routeMatch->method('getParameter')
      ->with('node')
      ->willReturn($node);

    $hooks = new PageAttachmentHooks($this->routeMatch);
    $attachments = [];
    $hooks->pageAttachments($attachments);
    $this->assertArrayNotHasKey('#attached', $attachments);
  }

  /**
   * Node parameter is a stanford_person — library is attached.
   */
  public function testNodeStanfordPerson() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('getType')->willReturn('stanford_person');

    $this->routeMatch->method('getParameter')
      ->with('node')
      ->willReturn($node);

    $hooks = new PageAttachmentHooks($this->routeMatch);
    $attachments = [];
    $hooks->pageAttachments($attachments);
    $this->assertContains('stanford_person/node', $attachments['#attached']['library']);
  }

}
