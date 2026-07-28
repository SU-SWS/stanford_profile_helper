<?php

declare(strict_types=1);

namespace Drupal\Tests\jumpstart_ui\Unit\Hook;

use Drupal\Core\Routing\AdminContext;
use Drupal\jumpstart_ui\Hook\PageHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for PageHooks.
 */
#[Group('jumpstart_ui')]
#[CoversClass(PageHooks::class)]
class PageHooksTest extends UnitTestCase {

  /**
   * Builds the hook object with a mocked admin context.
   */
  protected function buildHooks(bool $is_admin_route): PageHooks {
    $adminContext = $this->createMock(AdminContext::class);
    $adminContext->method('isAdminRoute')->willReturn($is_admin_route);
    return new PageHooks($adminContext);
  }

  /**
   * On non-admin routes, the libraries should be attached.
   */
  public function testPageAttachmentsOnNonAdminRoute(): void {
    $hooks = $this->buildHooks(FALSE);
    $page = [];
    $hooks->pageAttachments($page);

    $this->assertSame([
      'jumpstart_ui/base',
      'jumpstart_ui/layout',
      'jumpstart_ui/jumpstart_ui',
    ], $page['#attached']['library']);
  }

  /**
   * On admin routes, no libraries should be attached.
   */
  public function testPageAttachmentsOnAdminRoute(): void {
    $hooks = $this->buildHooks(TRUE);
    $page = [];
    $hooks->pageAttachments($page);

    $this->assertArrayNotHasKey('#attached', $page);
  }

}
