<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events\Unit\Hook;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_events\Hook\StanfordEventHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for StanfordEventHooks.
 */
#[Group('stanford_events')]
class StanfordEventHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_events\Hook\StanfordEventHooks
   */
  protected StanfordEventHooks $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new StanfordEventHooks();
  }

  /**
   * The end date is added to the attributes when the date has an end value.
   *
   * The library is always attached.
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
   * No attribute is added when the date field has no end value.
   *
   * The library is still attached.
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
   * No attribute is added when the node has no date field.
   *
   * The library is still attached.
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
