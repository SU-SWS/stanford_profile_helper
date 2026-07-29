<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Extension\ThemeHandlerInterface;
use Drupal\stanford_profile_helper\Hook\PatternHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for PatternHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(PatternHooks::class)]
class PatternHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\PatternHooks
   */
  protected PatternHooks $hooks;

  /**
   * Mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Mocked theme handler.
   *
   * @var \Drupal\Core\Extension\ThemeHandlerInterface
   */
  protected ThemeHandlerInterface $themeHandler;

  /**
   * Base directory for the temporary fixture tree used by these tests.
   *
   * @var string
   */
  protected string $fixtureRoot;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->themeHandler = $this->createMock(ThemeHandlerInterface::class);
    $this->hooks = new PatternHooks($this->moduleHandler, $this->themeHandler);

    $this->fixtureRoot = sys_get_temp_dir() . '/su_pattern_hooks_test_' . getmypid() . '_' . uniqid();
    mkdir($this->fixtureRoot, 0777, TRUE);
  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    $this->removeDirectory($this->fixtureRoot);
    parent::tearDown();
  }

  /**
   * Recursively deletes the given directory.
   */
  protected function removeDirectory(string $dir): void {
    if (!is_dir($dir)) {
      return;
    }
    foreach (scandir($dir) as $item) {
      if ($item === '.' || $item === '..') {
        continue;
      }
      $path = "$dir/$item";
      is_dir($path) ? $this->removeDirectory($path) : unlink($path);
    }
    rmdir($dir);
  }

  /**
   * Creates a nested directory path under the fixture root and returns it.
   */
  protected function makePath(string $relative): string {
    $path = $this->fixtureRoot . '/' . $relative;
    mkdir($path, 0777, TRUE);
    return $path;
  }

  /**
   * Calls the protected extensionEnabled() method via reflection.
   */
  protected function callExtensionEnabled(Extension $extension): bool {
    $method = new \ReflectionMethod(PatternHooks::class, 'extensionEnabled');
    $method->setAccessible(TRUE);
    return $method->invoke($this->hooks, $extension);
  }

  /**
   * Builds an Extension mock whose getPath() returns the given path.
   */
  protected function mockExtension(string $path): Extension {
    $extension = $this->createMock(Extension::class);
    $extension->method('getPath')->willReturn($path);
    return $extension;
  }

  /**
   * The component info alter hook currently performs no modifications since
   * the enabling check is commented out in the source; the array passed in
   * should remain completely unchanged.
   */
  public function testComponentInfoAlterIsNoop(): void {
    $components = ['foo' => ['id' => 'foo'], 'bar' => ['id' => 'bar']];
    $original = $components;
    $this->hooks->componentInfoAlter($components);
    $this->assertSame($original, $components);
  }

  /**
   * When no info.yml file is found anywhere up the directory tree, FALSE is
   * returned.
   */
  public function testExtensionEnabledReturnsFalseWhenNoInfoFileFound(): void {
    $deepPath = $this->makePath('no_info_anywhere/level1/level2');
    $extension = $this->mockExtension($deepPath);

    $this->moduleHandler->expects($this->never())->method('moduleExists');
    $this->themeHandler->expects($this->never())->method('themeExists');

    $this->assertFalse($this->callExtensionEnabled($extension));
  }

  /**
   * When an info.yml file is found without a 'type' key, the walk continues
   * up the tree until it eventually runs out of parents and returns FALSE.
   */
  public function testExtensionEnabledContinuesWhenInfoFileHasNoTypeKey(): void {
    $root = $this->makePath('no_type_key');
    $deepPath = $this->makePath('no_type_key/level1/level2');
    file_put_contents("$root/some_extension.info.yml", "name: 'Some Extension'\n");

    $extension = $this->mockExtension($deepPath);

    $this->moduleHandler->expects($this->never())->method('moduleExists');
    $this->themeHandler->expects($this->never())->method('themeExists');

    $this->assertFalse($this->callExtensionEnabled($extension));
  }

  /**
   * A theme info.yml file found up the tree causes a theme existence check.
   */
  public function testExtensionEnabledChecksThemeExistence(): void {
    $root = $this->makePath('theme_case');
    $deepPath = $this->makePath('theme_case/components/button');
    file_put_contents("$root/my_theme.info.yml", "type: theme\nname: 'My Theme'\n");

    $extension = $this->mockExtension($deepPath);

    $this->themeHandler->expects($this->once())
      ->method('themeExists')
      ->with('my_theme')
      ->willReturn(TRUE);
    $this->moduleHandler->expects($this->never())->method('moduleExists');

    $this->assertTrue($this->callExtensionEnabled($extension));
  }

  /**
   * When the theme does not exist, FALSE is returned.
   */
  public function testExtensionEnabledThemeDoesNotExist(): void {
    $root = $this->makePath('theme_case_disabled');
    file_put_contents("$root/my_theme.info.yml", "type: theme\nname: 'My Theme'\n");

    $extension = $this->mockExtension($root);

    $this->themeHandler->method('themeExists')->with('my_theme')->willReturn(FALSE);

    $this->assertFalse($this->callExtensionEnabled($extension));
  }

  /**
   * A module info.yml file found up the tree causes a module existence
   * check.
   */
  public function testExtensionEnabledChecksModuleExistence(): void {
    $root = $this->makePath('module_case');
    $deepPath = $this->makePath('module_case/components/card');
    file_put_contents("$root/my_module.info.yml", "type: module\nname: 'My Module'\n");

    $extension = $this->mockExtension($deepPath);

    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('my_module')
      ->willReturn(TRUE);
    $this->themeHandler->expects($this->never())->method('themeExists');

    $this->assertTrue($this->callExtensionEnabled($extension));
  }

  /**
   * A profile info.yml file found up the tree also causes a module existence
   * check (profiles are checked via the module handler).
   */
  public function testExtensionEnabledChecksProfileExistence(): void {
    $root = $this->makePath('profile_case');
    $deepPath = $this->makePath('profile_case/components/hero');
    file_put_contents("$root/my_profile.info.yml", "type: profile\nname: 'My Profile'\n");

    $extension = $this->mockExtension($deepPath);

    $this->moduleHandler->expects($this->once())
      ->method('moduleExists')
      ->with('my_profile')
      ->willReturn(FALSE);

    $this->assertFalse($this->callExtensionEnabled($extension));
  }

}
