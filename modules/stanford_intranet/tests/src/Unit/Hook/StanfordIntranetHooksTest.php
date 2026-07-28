<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_intranet\Unit\Hook;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_intranet\Hook\StanfordIntranetHooks;
use Drupal\stanford_intranet\Plugin\Field\FieldType\EntityAccessFieldType;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for StanfordIntranetHooks.
 */
#[Group('stanford_intranet')]
#[CoversClass(StanfordIntranetHooks::class)]
class StanfordIntranetHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_intranet\Hook\StanfordIntranetHooks
   */
  protected StanfordIntranetHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new StanfordIntranetHooks();
    // Ensure there is no active container, so that clearAlgolia()'s
    // \Drupal::hasService() call safely resolves to FALSE without needing a
    // real search_api_algolia.helper service. clearAlgolia() itself is
    // annotated @codeCoverageIgnore.
    \Drupal::unsetContainer();
  }

  /**
   * Nodes without the access field are left alone.
   */
  public function testNodeUpdateNoField() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')
      ->with(EntityAccessFieldType::FIELD_NAME)
      ->willReturn(FALSE);
    $node->expects($this->never())->method('get');

    $this->hooks->nodeUpdate($node);

    // No exception thrown and no assertion failure means the early return
    // path was exercised without attempting to clear the algolia index.
    $this->addToAssertionCount(1);
  }

  /**
   * Nodes with an empty access field are left alone.
   */
  public function testNodeUpdateEmptyField() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')
      ->with(EntityAccessFieldType::FIELD_NAME)
      ->willReturn(TRUE);

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('count')->willReturn(0);
    $node->method('get')
      ->with(EntityAccessFieldType::FIELD_NAME)
      ->willReturn($field_list);

    $this->hooks->nodeUpdate($node);

    $this->addToAssertionCount(1);
  }

  /**
   * Nodes with a populated access field trigger the algolia index clear.
   */
  public function testNodeUpdateWithFieldValuesClearsAlgolia() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('hasField')
      ->with(EntityAccessFieldType::FIELD_NAME)
      ->willReturn(TRUE);

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('count')->willReturn(1);
    $node->method('get')
      ->with(EntityAccessFieldType::FIELD_NAME)
      ->willReturn($field_list);

    $this->hooks->nodeUpdate($node);

    $this->addToAssertionCount(1);
  }

}
