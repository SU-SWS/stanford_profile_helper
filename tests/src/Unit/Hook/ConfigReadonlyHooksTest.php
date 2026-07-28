<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Site\Settings;
use Drupal\stanford_profile_helper\Hook\ConfigReadonlyHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for ConfigReadonlyHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(ConfigReadonlyHooks::class)]
class ConfigReadonlyHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\ConfigReadonlyHooks
   */
  protected ConfigReadonlyHooks $hooks;

  /**
   * Mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mocked route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * Mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Mocked messenger.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected MessengerInterface $messenger;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->currentUser = $this->createMock(AccountProxyInterface::class);
    $this->messenger = $this->createMock(MessengerInterface::class);
    $this->hooks = new ConfigReadonlyHooks($this->configFactory, $this->routeMatch, $this->currentUser, $this->messenger);
  }

  // -----------------------------------------------------------------------
  // configReadonlyWhitelistPatterns()
  // -----------------------------------------------------------------------

  /**
   * By default only the active theme settings are whitelisted.
   */
  public function testConfigReadonlyWhitelistPatternsDefaultTheme(): void {
    $themeConfig = $this->createMock(ImmutableConfig::class);
    $themeConfig->method('get')->with('default')->willReturn('stanford_basic');
    $this->configFactory->method('get')->with('system.theme')->willReturn($themeConfig);
    $this->routeMatch->method('getRouteName')->willReturn('some.unrelated.route');

    $patterns = $this->hooks->configReadonlyWhitelistPatterns();
    $this->assertSame(['stanford_basic.settings'], $patterns);
  }

  /**
   * A known route adds its associated config patterns to the whitelist.
   */
  public function testConfigReadonlyWhitelistPatternsKnownRoute(): void {
    $themeConfig = $this->createMock(ImmutableConfig::class);
    $themeConfig->method('get')->with('default')->willReturn('olivero');
    $this->configFactory->method('get')->with('system.theme')->willReturn($themeConfig);
    $this->routeMatch->method('getRouteName')->willReturn('entity.taxonomy_vocabulary.reset_form');

    $patterns = $this->hooks->configReadonlyWhitelistPatterns();
    $this->assertSame(['olivero.settings', 'taxonomy.vocabulary.*'], $patterns);
  }

  /**
   * Each of the other known routes also adds its config patterns.
   */
  public function testConfigReadonlyWhitelistPatternsSearchApiRoutes(): void {
    $themeConfig = $this->createMock(ImmutableConfig::class);
    $themeConfig->method('get')->with('default')->willReturn('olivero');
    $this->configFactory->method('get')->with('system.theme')->willReturn($themeConfig);

    foreach ([
      'entity.search_api_index.rebuild_tracker',
      'entity.search_api_index.clear',
      'entity.search_api_index.reindex',
    ] as $route) {
      $this->routeMatch = $this->createMock(RouteMatchInterface::class);
      $this->routeMatch->method('getRouteName')->willReturn($route);
      $this->hooks = new ConfigReadonlyHooks($this->configFactory, $this->routeMatch, $this->currentUser, $this->messenger);

      $patterns = $this->hooks->configReadonlyWhitelistPatterns();
      $this->assertSame(['olivero.settings', 'search_api.index.*'], $patterns);
    }
  }

  /**
   * The xmlsitemap rebuild route whitelists the xmlsitemap settings.
   */
  public function testConfigReadonlyWhitelistPatternsXmlsitemapRoute(): void {
    $themeConfig = $this->createMock(ImmutableConfig::class);
    $themeConfig->method('get')->with('default')->willReturn('olivero');
    $this->configFactory->method('get')->with('system.theme')->willReturn($themeConfig);
    $this->routeMatch->method('getRouteName')->willReturn('xmlsitemap.admin_rebuild');

    $patterns = $this->hooks->configReadonlyWhitelistPatterns();
    $this->assertSame(['olivero.settings', 'xmlsitemap.settings'], $patterns);
  }

  /**
   * A NULL route name (no matched route) is handled without errors.
   */
  public function testConfigReadonlyWhitelistPatternsNullRoute(): void {
    $themeConfig = $this->createMock(ImmutableConfig::class);
    $themeConfig->method('get')->with('default')->willReturn('olivero');
    $this->configFactory->method('get')->with('system.theme')->willReturn($themeConfig);
    $this->routeMatch->method('getRouteName')->willReturn(NULL);

    $patterns = $this->hooks->configReadonlyWhitelistPatterns();
    $this->assertSame(['olivero.settings'], $patterns);
  }

  // -----------------------------------------------------------------------
  // formMenuEditFormAlter()
  // -----------------------------------------------------------------------

  /**
   * When config_readonly setting is off, the form is left untouched.
   */
  public function testFormMenuEditFormAlterReadOnlyDisabled(): void {
    new Settings(['config_readonly' => FALSE]);
    $this->messenger->expects($this->never())->method('deleteByType');

    $form = ['label' => [], 'description' => [], 'id' => []];
    $form_state = $this->createMock('\Drupal\Core\Form\FormStateInterface');
    $this->hooks->formMenuEditFormAlter($form, $form_state);

    $this->assertArrayNotHasKey('#access', $form['label']);
    $this->assertArrayNotHasKey('#access', $form['description']);
    $this->assertArrayNotHasKey('#access', $form['id']);
  }

  /**
   * When the setting is unset, it defaults to disabled and skips the form.
   */
  public function testFormMenuEditFormAlterReadOnlySettingUnset(): void {
    new Settings([]);

    $form = ['label' => [], 'description' => [], 'id' => []];
    $form_state = $this->createMock('\Drupal\Core\Form\FormStateInterface');
    $this->hooks->formMenuEditFormAlter($form, $form_state);

    $this->assertArrayNotHasKey('#access', $form['label']);
  }

  /**
   * With config_readonly and the permission, fields are accessible and the
   * warning message is left intact.
   */
  public function testFormMenuEditFormAlterReadOnlyWithPermission(): void {
    new Settings(['config_readonly' => TRUE]);
    $this->currentUser->method('hasPermission')
      ->with('Administer menus and menu items')
      ->willReturn(TRUE);
    $this->messenger->expects($this->never())->method('deleteByType');

    $form = ['label' => [], 'description' => [], 'id' => []];
    $form_state = $this->createMock('\Drupal\Core\Form\FormStateInterface');
    $this->hooks->formMenuEditFormAlter($form, $form_state);

    $this->assertTrue($form['label']['#access']);
    $this->assertTrue($form['description']['#access']);
    $this->assertTrue($form['id']['#access']);
  }

  /**
   * With config_readonly and no permission, fields are hidden and the
   * warning message is removed.
   */
  public function testFormMenuEditFormAlterReadOnlyWithoutPermission(): void {
    new Settings(['config_readonly' => TRUE]);
    $this->currentUser->method('hasPermission')
      ->with('Administer menus and menu items')
      ->willReturn(FALSE);
    $this->messenger->expects($this->once())
      ->method('deleteByType')
      ->with('warning');

    $form = ['label' => [], 'description' => [], 'id' => []];
    $form_state = $this->createMock('\Drupal\Core\Form\FormStateInterface');
    $this->hooks->formMenuEditFormAlter($form, $form_state);

    $this->assertFalse($form['label']['#access']);
    $this->assertFalse($form['description']['#access']);
    $this->assertFalse($form['id']['#access']);
  }

}
