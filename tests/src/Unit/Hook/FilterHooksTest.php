<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\stanford_profile_helper\Hook\FilterHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for FilterHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(FilterHooks::class)]
class FilterHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\FilterHooks
   */
  protected FilterHooks $hooks;

  /**
   * Mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->hooks = new FilterHooks($this->moduleHandler);
  }

  // -----------------------------------------------------------------------
  // filterInfoAlter()
  // -----------------------------------------------------------------------

  /**
   * When the mathjax module is enabled, the filter class is overridden.
   */
  public function testFilterInfoAlterSetsMathjaxClassWhenModuleEnabled(): void {
    $this->moduleHandler->method('moduleExists')->with('mathjax')->willReturn(TRUE);

    $info = ['filter_mathjax' => ['class' => 'Original\\Class']];
    $this->hooks->filterInfoAlter($info);

    $this->assertSame('Drupal\stanford_profile_helper\Plugin\Filter\Mathjax', $info['filter_mathjax']['class']);
  }

  /**
   * When the mathjax module is not enabled, the filter class is untouched.
   */
  public function testFilterInfoAlterSkipsWhenMathjaxModuleDisabled(): void {
    $this->moduleHandler->method('moduleExists')->with('mathjax')->willReturn(FALSE);

    $info = ['filter_mathjax' => ['class' => 'Original\\Class']];
    $this->hooks->filterInfoAlter($info);

    $this->assertSame('Original\\Class', $info['filter_mathjax']['class']);
  }

  /**
   * When the filter_mathjax key isn't present, nothing happens.
   */
  public function testFilterInfoAlterSkipsWhenFilterMathjaxKeyMissing(): void {
    $this->moduleHandler->expects($this->never())->method('moduleExists');

    $info = ['some_other_filter' => []];
    $this->hooks->filterInfoAlter($info);

    $this->assertSame(['some_other_filter' => []], $info);
  }

  // -----------------------------------------------------------------------
  // filterFormatAccess()
  // -----------------------------------------------------------------------

  /**
   * The administrative_html filter format is always forbidden.
   */
  public function testFilterFormatAccessForbidsAdministrativeHtml(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('id')->willReturn('administrative_html');
    $account = $this->createMock(AccountInterface::class);

    $result = $this->hooks->filterFormatAccess($entity, 'use', $account);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Other filter formats are neutral.
   */
  public function testFilterFormatAccessNeutralForOtherFormats(): void {
    $entity = $this->createMock(EntityInterface::class);
    $entity->method('id')->willReturn('basic_html');
    $account = $this->createMock(AccountInterface::class);

    $result = $this->hooks->filterFormatAccess($entity, 'use', $account);
    $this->assertFalse($result->isForbidden());
  }

}
