<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\stanford_profile_helper\Hook\FieldGroupHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for FieldGroupHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(FieldGroupHooks::class)]
class FieldGroupHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\FieldGroupHooks
   */
  protected FieldGroupHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new FieldGroupHooks();
  }

  /**
   * When the lockup options group is present, #states are added to both
   * the lockup options and logo image elements.
   */
  public function testFieldGroupFormProcessBuildAlterAddsStates(): void {
    $element = [
      'group_lockup_options' => [],
      'group_logo_image' => [],
    ];

    $this->hooks->fieldGroupFormProcessBuildAlter($element);

    $expected_states = [
      'visible' => [
        ':input[name="su_lockup_enabled[value]"]' => [
          'checked' => FALSE,
        ],
      ],
    ];
    $this->assertSame($expected_states, $element['group_lockup_options']['#states']);
    $this->assertSame($expected_states, $element['group_logo_image']['#states']);
  }

  /**
   * When the lockup options group is absent, the element is untouched.
   */
  public function testFieldGroupFormProcessBuildAlterNoLockupGroup(): void {
    $element = ['some_other_group' => []];
    $this->hooks->fieldGroupFormProcessBuildAlter($element);
    $this->assertSame(['some_other_group' => []], $element);
  }

}
