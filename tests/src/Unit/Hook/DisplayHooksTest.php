<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\stanford_profile_helper\Hook\DisplayHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for DisplayHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(DisplayHooks::class)]
class DisplayHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\DisplayHooks
   */
  protected DisplayHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new DisplayHooks();
  }

  /**
   * The title component is removed for node search indexing view modes.
   */
  public function testEntityViewDisplayAlterRemovesTitleForNodeSearchIndexing(): void {
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $display->expects($this->once())
      ->method('removeComponent')
      ->with('title');

    $context = ['view_mode' => 'search_indexing_full', 'entity_type' => 'node'];
    $this->hooks->entityViewDisplayAlter($display, $context);
  }

  /**
   * Non-node entities are not touched, even with a search_indexing mode.
   */
  public function testEntityViewDisplayAlterSkipsNonNodeEntities(): void {
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $display->expects($this->never())->method('removeComponent');

    $context = ['view_mode' => 'search_indexing_full', 'entity_type' => 'media'];
    $this->hooks->entityViewDisplayAlter($display, $context);
  }

  /**
   * View modes that don't reference search_indexing are not touched.
   */
  public function testEntityViewDisplayAlterSkipsOtherViewModes(): void {
    $display = $this->createMock(EntityViewDisplayInterface::class);
    $display->expects($this->never())->method('removeComponent');

    $context = ['view_mode' => 'teaser', 'entity_type' => 'node'];
    $this->hooks->entityViewDisplayAlter($display, $context);
  }

}
