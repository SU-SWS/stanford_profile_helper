<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_courses\Unit\Hook;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\node\NodeInterface;
use Drupal\pathauto\PathautoPatternInterface;
use Drupal\stanford_courses\Hook\NodeHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for NodeHooks.
 */
#[Group('stanford_courses')]
#[CoversClass(NodeHooks::class)]
class NodeHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_courses\Hook\NodeHooks
   */
  protected NodeHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new NodeHooks();
  }

  /**
   * Builds a mocked node with the given field item counts.
   */
  protected function createNodeMock(int $subjectCount, int $codeCount): NodeInterface {
    $subjectField = $this->createMock(FieldItemListInterface::class);
    $subjectField->method('count')->willReturn($subjectCount);

    $codeField = $this->createMock(FieldItemListInterface::class);
    $codeField->method('count')->willReturn($codeCount);

    $node = $this->createMock(NodeInterface::class);
    $node->method('get')->willReturnMap([
      ['su_course_subject', $subjectField],
      ['su_course_code', $codeField],
    ]);

    return $node;
  }

  /**
   * No 'node' key in context data — nothing happens, no error.
   */
  public function testPathautoPatternAlterNoNodeInContext() {
    $pattern = $this->createMock(PathautoPatternInterface::class);
    $pattern->expects($this->never())->method('setPattern');

    $context = ['bundle' => 'stanford_course', 'data' => []];
    $this->hooks->pathautoPatternAlter($pattern, $context);
  }

  /**
   * Bundle is not 'stanford_course' — pattern is left untouched.
   */
  public function testPathautoPatternAlterWrongBundle() {
    $node = $this->createNodeMock(0, 0);

    $pattern = $this->createMock(PathautoPatternInterface::class);
    $pattern->expects($this->never())->method('setPattern');

    $context = ['bundle' => 'some_other_bundle', 'data' => ['node' => $node]];
    $this->hooks->pathautoPatternAlter($pattern, $context);
  }

  /**
   * Node has a course subject value — pattern should not be overridden.
   */
  public function testPathautoPatternAlterHasSubject() {
    $node = $this->createNodeMock(1, 0);

    $pattern = $this->createMock(PathautoPatternInterface::class);
    $pattern->expects($this->never())->method('setPattern');

    $context = ['bundle' => 'stanford_course', 'data' => ['node' => $node]];
    $this->hooks->pathautoPatternAlter($pattern, $context);
  }

  /**
   * Node has a course code value — pattern should not be overridden.
   */
  public function testPathautoPatternAlterHasCode() {
    $node = $this->createNodeMock(0, 1);

    $pattern = $this->createMock(PathautoPatternInterface::class);
    $pattern->expects($this->never())->method('setPattern');

    $context = ['bundle' => 'stanford_course', 'data' => ['node' => $node]];
    $this->hooks->pathautoPatternAlter($pattern, $context);
  }

  /**
   * Node has neither subject nor code — pattern is overridden to /courses/.
   */
  public function testPathautoPatternAlterNoSubjectOrCode() {
    $node = $this->createNodeMock(0, 0);

    $pattern = $this->createMock(PathautoPatternInterface::class);
    $pattern->expects($this->once())
      ->method('setPattern')
      ->with('/courses/[node:title]');

    $context = ['bundle' => 'stanford_course', 'data' => ['node' => $node]];
    $this->hooks->pathautoPatternAlter($pattern, $context);
  }

}
