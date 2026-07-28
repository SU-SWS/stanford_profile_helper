<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\stanford_profile_helper\Hook\ParagraphHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for ParagraphHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(ParagraphHooks::class)]
class ParagraphHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\ParagraphHooks
   */
  protected ParagraphHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new ParagraphHooks();
  }

  /**
   * When the group is 'paragraph', the delete and clone links are removed.
   */
  public function testContextualLinksAlterRemovesLinksForParagraphGroup(): void {
    $links = [
      'paragraphs_edit.delete_form' => ['title' => 'Delete'],
      'paragraphs_edit.clone_form' => ['title' => 'Clone'],
      'some_other.link' => ['title' => 'Keep me'],
    ];

    $this->hooks->contextualLinksAlter($links, 'paragraph', []);

    $this->assertArrayNotHasKey('paragraphs_edit.delete_form', $links);
    $this->assertArrayNotHasKey('paragraphs_edit.clone_form', $links);
    $this->assertArrayHasKey('some_other.link', $links);
  }

  /**
   * When the group is not 'paragraph', links are left untouched.
   */
  public function testContextualLinksAlterLeavesOtherGroupsUntouched(): void {
    $links = [
      'paragraphs_edit.delete_form' => ['title' => 'Delete'],
      'paragraphs_edit.clone_form' => ['title' => 'Clone'],
    ];

    $this->hooks->contextualLinksAlter($links, 'node', []);

    $this->assertArrayHasKey('paragraphs_edit.delete_form', $links);
    $this->assertArrayHasKey('paragraphs_edit.clone_form', $links);
  }

  /**
   * When the links to remove don't exist, unset() is a no-op.
   */
  public function testContextualLinksAlterMissingLinksIsNoop(): void {
    $links = ['some_other.link' => ['title' => 'Keep me']];

    $this->hooks->contextualLinksAlter($links, 'paragraph', []);

    $this->assertSame(['some_other.link' => ['title' => 'Keep me']], $links);
  }

}
