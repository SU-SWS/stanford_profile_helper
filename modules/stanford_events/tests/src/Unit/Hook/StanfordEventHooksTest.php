<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events\Unit\Hook;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_events\Hook\StanfordEventHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for StanfordEventHooks.
 */
#[Group('stanford_events')]
#[CoversClass(StanfordEventHooks::class)]
class StanfordEventHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_events\Hook\StanfordEventHooks
   */
  protected StanfordEventHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new StanfordEventHooks();
  }

  /**
   * When the node has the date field with an end value, the end date is
   * attached to the attributes, and the library is always attached.
   */
  public function testPreprocessNodeStanfordEventFullWithEndDate(): void {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('getValue')->willReturn([
      ['end_value' => '2026-08-01T00:00:00'],
    ]);

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_event_date_time')->willReturn(TRUE);
    $node->method('get')->with('su_event_date_time')->willReturn($field);

    $variables = [
      'node' => $node,
      'attributes' => [],
    ];

    $this->hooks->preprocessNodeStanfordEventFull($variables);

    $this->assertEquals('2026-08-01T00:00:00', $variables['attributes']['data-end-date']);
    $this->assertSame(['stanford_events/event_node'], $variables['#attached']['library']);
  }

  /**
   * When the date field exists but has no end value, no attribute is added
   * but the library is still attached.
   */
  public function testPreprocessNodeStanfordEventFullEmptyEndDate(): void {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('getValue')->willReturn([
      ['end_value' => ''],
    ]);

    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_event_date_time')->willReturn(TRUE);
    $node->method('get')->with('su_event_date_time')->willReturn($field);

    $variables = [
      'node' => $node,
      'attributes' => [],
    ];

    $this->hooks->preprocessNodeStanfordEventFull($variables);

    $this->assertArrayNotHasKey('data-end-date', $variables['attributes']);
    $this->assertSame(['stanford_events/event_node'], $variables['#attached']['library']);
  }

  /**
   * When the node doesn't have the date field at all, no attribute is added
   * but the library is still attached.
   */
  public function testPreprocessNodeStanfordEventFullNoDateField(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')->with('su_event_date_time')->willReturn(FALSE);
    $node->expects($this->never())->method('get');

    $variables = [
      'node' => $node,
      'attributes' => [],
    ];

    $this->hooks->preprocessNodeStanfordEventFull($variables);

    $this->assertArrayNotHasKey('data-end-date', $variables['attributes']);
    $this->assertSame(['stanford_events/event_node'], $variables['#attached']['library']);
  }

}
