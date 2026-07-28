<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\stanford_profile_helper\Hook\LayoutBuilderHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for LayoutBuilderHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(LayoutBuilderHooks::class)]
class LayoutBuilderHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\LayoutBuilderHooks
   */
  protected LayoutBuilderHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new LayoutBuilderHooks();
    $this->hooks->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Blocks provided by the menu_block module get re-categorized.
   */
  public function testPluginFilterBlockLayoutBuilderAlterRecategorizesMenuBlocks(): void {
    $definitions = [
      'menu_block:main' => ['provider' => 'menu_block', 'category' => 'Menus'],
    ];

    $this->hooks->pluginFilterBlockLayoutBuilderAlter($definitions, []);

    $this->assertEquals('Menu Block', (string) $definitions['menu_block:main']['category']);
  }

  /**
   * Blocks from other providers are left untouched.
   */
  public function testPluginFilterBlockLayoutBuilderAlterSkipsOtherProviders(): void {
    $definitions = [
      'system_powered_by_block' => ['provider' => 'system', 'category' => 'System'],
    ];

    $this->hooks->pluginFilterBlockLayoutBuilderAlter($definitions, []);

    $this->assertSame('System', $definitions['system_powered_by_block']['category']);
  }

  /**
   * Multiple definitions are each processed independently.
   */
  public function testPluginFilterBlockLayoutBuilderAlterMultipleDefinitions(): void {
    $definitions = [
      'menu_block:main' => ['provider' => 'menu_block', 'category' => 'Menus'],
      'menu_block:footer' => ['provider' => 'menu_block', 'category' => 'Menus'],
      'system_powered_by_block' => ['provider' => 'system', 'category' => 'System'],
    ];

    $this->hooks->pluginFilterBlockLayoutBuilderAlter($definitions, []);

    $this->assertEquals('Menu Block', (string) $definitions['menu_block:main']['category']);
    $this->assertEquals('Menu Block', (string) $definitions['menu_block:footer']['category']);
    $this->assertSame('System', $definitions['system_powered_by_block']['category']);
  }

  /**
   * Empty definitions array runs cleanly with no changes.
   */
  public function testPluginFilterBlockLayoutBuilderAlterEmptyDefinitions(): void {
    $definitions = [];
    $this->hooks->pluginFilterBlockLayoutBuilderAlter($definitions, []);
    $this->assertSame([], $definitions);
  }

}
