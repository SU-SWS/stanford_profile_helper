<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Template\Attribute;
use Drupal\stanford_profile_helper\Hook\ThemeHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for ThemeHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(ThemeHooks::class)]
class ThemeHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\ThemeHooks
   */
  protected ThemeHooks $hooks;

  /**
   * Mocked module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected ModuleHandlerInterface $moduleHandler;

  /**
   * Mocked route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->hooks = new ThemeHooks($this->moduleHandler, $this->routeMatch);
    $this->hooks->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Default environment is used when neither acquia_purge nor acsf exist.
   */
  public function testPreprocessHtmlDefaultEnvironment(): void {
    $this->moduleHandler->method('moduleExists')->willReturn(FALSE);

    $variables = ['attributes' => ['class' => []]];
    $this->hooks->preprocessHtml($variables);

    $this->assertSame(['sws-other'], $variables['attributes']['class']);
  }

  /**
   * When acquia_purge is enabled, the sws-acquia class is added.
   */
  public function testPreprocessHtmlAcquiaPurgeEnvironment(): void {
    $this->moduleHandler->method('moduleExists')
      ->willReturnMap([
        ['acquia_purge', TRUE],
        ['acsf', FALSE],
      ]);

    $variables = ['attributes' => ['class' => []]];
    $this->hooks->preprocessHtml($variables);

    $this->assertSame(['sws-acquia'], $variables['attributes']['class']);
  }

  /**
   * When acsf is enabled, the sws-acsf class is added, overriding acquia.
   */
  public function testPreprocessHtmlAcsfEnvironment(): void {
    $this->moduleHandler->method('moduleExists')
      ->willReturnMap([
        ['acquia_purge', TRUE],
        ['acsf', TRUE],
      ]);

    $variables = ['attributes' => ['class' => []]];
    $this->hooks->preprocessHtml($variables);

    $this->assertSame(['sws-acsf'], $variables['attributes']['class']);
  }

  /**
   * The theme registry alter adds the field_name variable definition.
   */
  public function testThemeRegistryAlter(): void {
    $theme_registry = ['cshs_term_group' => ['variables' => []]];
    $this->hooks->themeRegistryAlter($theme_registry);

    $this->assertSame('', $theme_registry['cshs_term_group']['variables']['field_name']);
  }

  /**
   * The field name is added to each item in the entity reference field.
   */
  public function testPreprocessFieldEntityReference(): void {
    $variables = [
      'field_name' => 'field_foo',
      'items' => [
        ['content' => []],
        ['content' => []],
      ],
    ];
    $this->hooks->preprocessFieldEntityReference($variables);

    $this->assertSame('field_foo', $variables['items'][0]['content']['#field_name']);
    $this->assertSame('field_foo', $variables['items'][1]['content']['#field_name']);
  }

  /**
   * Theme suggestions are keyed off of the field name variable.
   */
  public function testThemeSuggestionsCshsTermGroup(): void {
    $suggestions = $this->hooks->themeSuggestionsCshsTermGroup(['field_name' => 'field_bar']);
    $this->assertSame(['cshs_term_group__field_bar'], $suggestions);
  }

  /**
   * The theme() hook returns the rabbit_hole_message theme definition.
   */
  public function testTheme(): void {
    $themes = $this->hooks->theme([], '', '', '');
    $this->assertArrayHasKey('rabbit_hole_message', $themes);
    $this->assertSame(['destination' => NULL], $themes['rabbit_hole_message']['variables']);
  }

  /**
   * The views.ajax library gets an additional dependency.
   */
  public function testLibraryInfoAlterViews(): void {
    $libraries = ['views.ajax' => ['dependencies' => []]];
    $this->hooks->libraryInfoAlter($libraries, 'views');

    $this->assertContains('stanford_profile_helper/ajax_views', $libraries['views.ajax']['dependencies']);
  }

  /**
   * The mathjax source library gets a dependency and setup/config are unset.
   */
  public function testLibraryInfoAlterMathjax(): void {
    $libraries = [
      'source' => ['dependencies' => []],
      'setup' => ['dependencies' => []],
      'config' => ['dependencies' => []],
    ];
    $this->hooks->libraryInfoAlter($libraries, 'mathjax');

    $this->assertContains('stanford_profile_helper/mathjax', $libraries['source']['dependencies']);
    $this->assertArrayNotHasKey('setup', $libraries);
    $this->assertArrayNotHasKey('config', $libraries);
  }

  /**
   * The fontawesome library is removed when fontawesome module is enabled.
   */
  public function testLibraryInfoAlterStanfordBasicWithFontawesomeEnabled(): void {
    $this->moduleHandler->method('moduleExists')
      ->with('fontawesome')
      ->willReturn(TRUE);

    $libraries = ['fontawesome' => ['dependencies' => []]];
    $this->hooks->libraryInfoAlter($libraries, 'stanford_basic');

    $this->assertArrayNotHasKey('fontawesome', $libraries);
  }

  /**
   * The fontawesome library is kept when fontawesome module is disabled.
   */
  public function testLibraryInfoAlterStanfordBasicWithFontawesomeDisabled(): void {
    $this->moduleHandler->method('moduleExists')
      ->with('fontawesome')
      ->willReturn(FALSE);

    $libraries = ['fontawesome' => ['dependencies' => []]];
    $this->hooks->libraryInfoAlter($libraries, 'stanford_basic');

    $this->assertArrayHasKey('fontawesome', $libraries);
  }

  /**
   * The pdb_react/react dependency is stripped from every library.
   */
  public function testLibraryInfoAlterStripsPdbReactDependency(): void {
    $libraries = [
      'foo' => ['dependencies' => ['pdb_react/react', 'core/drupal']],
      'bar' => [],
    ];
    $this->hooks->libraryInfoAlter($libraries, 'some_other_extension');

    $this->assertSame(['core/drupal'], array_values($libraries['foo']['dependencies']));
    $this->assertArrayNotHasKey('dependencies', $libraries['bar']);
  }

  /**
   * Toolbar tabs get a class based on their key.
   */
  public function testPreprocessToolbar(): void {
    $attributes = $this->getMockBuilder(Attribute::class)
      ->onlyMethods(['addClass'])
      ->getMock();
    $attributes->expects($this->once())
      ->method('addClass')
      ->with('administration-menu-tab')
      ->willReturnSelf();

    $variables = [
      'tabs' => [
        'administration-menu' => ['attributes' => $attributes],
        'no-attributes' => [],
      ],
    ];
    $this->hooks->preprocessToolbar($variables);
  }

  /**
   * The help content is removed for the help.main route.
   */
  public function testPreprocessBlockHelpOnHelpMainRoute(): void {
    $this->routeMatch->method('getRouteName')->willReturn('help.main');

    $variables = ['content' => ['#markup' => 'help text']];
    $this->hooks->preprocessBlockHelp($variables);

    $this->assertArrayNotHasKey('content', $variables);
  }

  /**
   * The help content is left alone on other routes.
   */
  public function testPreprocessBlockHelpOnOtherRoute(): void {
    $this->routeMatch->method('getRouteName')->willReturn('some.other.route');

    $variables = ['content' => ['#markup' => 'help text']];
    $this->hooks->preprocessBlockHelp($variables);

    $this->assertArrayHasKey('content', $variables);
  }

  /**
   * The hook_help section title is overridden.
   */
  public function testHelpSectionInfoAlter(): void {
    $info = ['hook_help' => ['title' => 'Module overviews']];
    $this->hooks->helpSectionInfoAlter($info);

    $this->assertSame('For Developers', (string) $info['hook_help']['title']);
  }

}
