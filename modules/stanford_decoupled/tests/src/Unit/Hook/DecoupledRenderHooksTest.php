<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_decoupled\Unit\Hook;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\stanford_decoupled\Hook\DecoupledRenderHooks;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for DecoupledRenderHooks.
 */
#[Group('stanford_decoupled')]
#[CoversClass(DecoupledRenderHooks::class)]
class DecoupledRenderHooksTest extends UnitTestCase {

  /**
   * The route match mock.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The cache backend mock used by DecoupledConfigOverrides::isDecoupled().
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected CacheBackendInterface $cache;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->routeMatch = $this->createMock(RouteMatchInterface::class);
    $this->cache = $this->createMock(CacheBackendInterface::class);

    $container = new ContainerBuilder();
    $container->set('cache.default', $this->cache);
    \Drupal::setContainer($container);
  }

  /**
   * Sets whether DecoupledConfigOverrides::isDecoupled() returns TRUE/FALSE.
   */
  protected function setDecoupled(bool $decoupled): void {
    $this->cache->method('get')
      ->with('stanford_decoupled')
      ->willReturn((object) ['data' => $decoupled]);
  }

  /**
   * Not decoupled: preprocessImage should not touch attributes.
   */
  public function testPreprocessImageNotDecoupled(): void {
    $this->setDecoupled(FALSE);

    $variables = [
      'uri' => '/core/misc/druplicon.png',
      'width' => NULL,
      'height' => NULL,
      'attributes' => [],
    ];

    $hooks = new DecoupledRenderHooks($this->routeMatch);
    $hooks->preprocessImage($variables);

    $this->assertArrayNotHasKey('data-width', $variables['attributes']);
    $this->assertArrayNotHasKey('data-height', $variables['attributes']);
  }

  /**
   * Decoupled, but width/height are already set: should not be overridden.
   */
  public function testPreprocessImageDecoupledWithExistingDimensions(): void {
    $this->setDecoupled(TRUE);

    $variables = [
      'uri' => '/core/misc/druplicon.png',
      'width' => 50,
      'height' => 50,
      'attributes' => [],
    ];

    $hooks = new DecoupledRenderHooks($this->routeMatch);
    $hooks->preprocessImage($variables);

    $this->assertArrayNotHasKey('data-width', $variables['attributes']);
    $this->assertArrayNotHasKey('data-height', $variables['attributes']);
  }

  /**
   * Decoupled, missing dimensions, valid absolute (leading slash) uri:
   * getimagesize() succeeds and populates the data-width/data-height attrs.
   */
  public function testPreprocessImageDecoupledSetsDimensions(): void {
    $this->setDecoupled(TRUE);

    $variables = [
      'uri' => '/core/misc/druplicon.png',
      'width' => NULL,
      'height' => NULL,
      'attributes' => [],
    ];

    $hooks = new DecoupledRenderHooks($this->routeMatch);
    $hooks->preprocessImage($variables);

    $this->assertSame(88, $variables['attributes']['data-width']);
    $this->assertSame(100, $variables['attributes']['data-height']);
  }

  /**
   * Decoupled, missing dimensions, a uri with query string is trimmed before
   * being passed to getimagesize().
   */
  public function testPreprocessImageDecoupledStripsQueryString(): void {
    $this->setDecoupled(TRUE);

    $variables = [
      'uri' => '/core/misc/druplicon.png?itok=abc123',
      'width' => '',
      'height' => '',
      'attributes' => [],
    ];

    $hooks = new DecoupledRenderHooks($this->routeMatch);
    $hooks->preprocessImage($variables);

    $this->assertSame(88, $variables['attributes']['data-width']);
    $this->assertSame(100, $variables['attributes']['data-height']);
  }

  /**
   * Decoupled, missing dimensions, uri does not resolve to a real image:
   * getimagesize() fails and no attributes are set.
   */
  public function testPreprocessImageDecoupledGetImageSizeFails(): void {
    $this->setDecoupled(TRUE);

    $variables = [
      'uri' => '/does/not/exist/image.png',
      'width' => NULL,
      'height' => NULL,
      'attributes' => [],
    ];

    $hooks = new DecoupledRenderHooks($this->routeMatch);
    $hooks->preprocessImage($variables);

    $this->assertArrayNotHasKey('data-width', $variables['attributes']);
    $this->assertArrayNotHasKey('data-height', $variables['attributes']);
  }

  /**
   * Decoupled, missing dimensions, uri without a leading slash is used
   * directly (not prefixed with DRUPAL_ROOT), resolved relative to the
   * current working directory.
   */
  public function testPreprocessImageDecoupledUriWithoutLeadingSlash(): void {
    $this->setDecoupled(TRUE);

    $variables = [
      'uri' => 'core/misc/druplicon.png',
      'width' => NULL,
      'height' => NULL,
      'attributes' => [],
    ];

    // The uri is resolved relative to the working directory (it is used
    // verbatim, unlike the leading-slash case which prefixes DRUPAL_ROOT),
    // so make sure the working directory is DRUPAL_ROOT for this assertion.
    $previousCwd = getcwd();
    chdir(DRUPAL_ROOT);
    try {
      $hooks = new DecoupledRenderHooks($this->routeMatch);
      $hooks->preprocessImage($variables);
    }
    finally {
      chdir($previousCwd);
    }

    $this->assertSame(88, $variables['attributes']['data-width']);
    $this->assertSame(100, $variables['attributes']['data-height']);
  }

  /**
   * Route is not the node canonical route: return early, limit untouched.
   */
  public function testViewsPreExecuteWrongRoute(): void {
    $this->routeMatch->method('getRouteName')->willReturn('some.other.route');
    $this->setDecoupled(TRUE);

    $query = $this->createMock(QueryPluginBase::class);
    $query->expects($this->never())->method('setLimit');

    $view = $this->createMock(ViewExecutable::class);
    $view->query = $query;

    $hooks = new DecoupledRenderHooks($this->routeMatch);
    $hooks->viewsPreExecute($view);
  }

  /**
   * Node canonical route but site is not decoupled: return early.
   */
  public function testViewsPreExecuteNotDecoupled(): void {
    $this->routeMatch->method('getRouteName')->willReturn('entity.node.canonical');
    $this->setDecoupled(FALSE);

    $query = $this->createMock(QueryPluginBase::class);
    $query->expects($this->never())->method('setLimit');

    $view = $this->createMock(ViewExecutable::class);
    $view->query = $query;

    $hooks = new DecoupledRenderHooks($this->routeMatch);
    $hooks->viewsPreExecute($view);
  }

  /**
   * Node canonical route, decoupled, current limit is 0 (disabled):
   * limit should be forced to 30.
   */
  public function testViewsPreExecuteLimitZeroIsCapped(): void {
    $this->routeMatch->method('getRouteName')->willReturn('entity.node.canonical');
    $this->setDecoupled(TRUE);

    $query = $this->createMock(QueryPluginBase::class);
    $query->method('getLimit')->willReturn(0);
    $query->expects($this->once())->method('setLimit')->with(30);

    $view = $this->createMock(ViewExecutable::class);
    $view->query = $query;

    $hooks = new DecoupledRenderHooks($this->routeMatch);
    $hooks->viewsPreExecute($view);
  }

  /**
   * Node canonical route, decoupled, current limit exceeds 5: capped to 30.
   */
  public function testViewsPreExecuteLimitTooHighIsCapped(): void {
    $this->routeMatch->method('getRouteName')->willReturn('entity.node.canonical');
    $this->setDecoupled(TRUE);

    $query = $this->createMock(QueryPluginBase::class);
    $query->method('getLimit')->willReturn(20);
    $query->expects($this->once())->method('setLimit')->with(30);

    $view = $this->createMock(ViewExecutable::class);
    $view->query = $query;

    $hooks = new DecoupledRenderHooks($this->routeMatch);
    $hooks->viewsPreExecute($view);
  }

  /**
   * Node canonical route, decoupled, current limit within 1-5: left alone.
   */
  public function testViewsPreExecuteLimitWithinRangeIsUntouched(): void {
    $this->routeMatch->method('getRouteName')->willReturn('entity.node.canonical');
    $this->setDecoupled(TRUE);

    $query = $this->createMock(QueryPluginBase::class);
    $query->method('getLimit')->willReturn(3);
    $query->expects($this->never())->method('setLimit');

    $view = $this->createMock(ViewExecutable::class);
    $view->query = $query;

    $hooks = new DecoupledRenderHooks($this->routeMatch);
    $hooks->viewsPreExecute($view);
  }

  /**
   * Decoupled: preprocessFileLink() sets the link url to absolute.
   */
  public function testPreprocessFileLinkDecoupled(): void {
    $this->setDecoupled(TRUE);

    $url = $this->createMock(Url::class);
    $url->expects($this->once())->method('setAbsolute');

    $variables = ['link' => ['#url' => $url]];

    $hooks = new DecoupledRenderHooks($this->routeMatch);
    $hooks->preprocessFileLink($variables);
  }

  /**
   * Not decoupled: preprocessFileLink() leaves the url alone.
   */
  public function testPreprocessFileLinkNotDecoupled(): void {
    $this->setDecoupled(FALSE);

    $url = $this->createMock(Url::class);
    $url->expects($this->never())->method('setAbsolute');

    $variables = ['link' => ['#url' => $url]];

    $hooks = new DecoupledRenderHooks($this->routeMatch);
    $hooks->preprocessFileLink($variables);
  }

}
