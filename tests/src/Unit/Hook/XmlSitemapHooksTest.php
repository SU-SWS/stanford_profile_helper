<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\State\StateInterface;
use Drupal\stanford_profile_helper\Hook\XmlSitemapHooks;
use Drupal\Tests\UnitTestCase;
use Drupal\xmlsitemap\XmlSitemapInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for XmlSitemapHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(XmlSitemapHooks::class)]
class XmlSitemapHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\XmlSitemapHooks
   */
  protected XmlSitemapHooks $hooks;

  /**
   * Mocked config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mocked state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected StateInterface $state;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->state = $this->createMock(StateInterface::class);
    $this->hooks = new XmlSitemapHooks($this->configFactory, $this->state);
  }

  /**
   * Sets up the system.site config mock with the given 403/404 pages.
   */
  protected function mockSiteConfig(string $page403, string $page404): void {
    $siteConfig = $this->createMock(ImmutableConfig::class);
    $siteConfig->method('get')
      ->willReturnMap([
        ['page.403', $page403],
        ['page.404', $page404],
      ]);
    $this->configFactory->method('get')
      ->with('system.site')
      ->willReturn($siteConfig);
  }

  /**
   * When the link matches the 403 page, its status is set to 0.
   */
  public function testXmlsitemapLinkAlterMatches403Page(): void {
    $this->mockSiteConfig('/node/1', '/node/2');

    $link = ['loc' => '/node/1', 'status' => 1];
    $this->hooks->xmlsitemapLinkAlter($link, []);

    $this->assertSame(0, $link['status']);
  }

  /**
   * When the link matches the 404 page, its status is set to 0.
   */
  public function testXmlsitemapLinkAlterMatches404Page(): void {
    $this->mockSiteConfig('/node/1', '/node/2');

    $link = ['loc' => '/node/2', 'status' => 1];
    $this->hooks->xmlsitemapLinkAlter($link, []);

    $this->assertSame(0, $link['status']);
  }

  /**
   * When the link matches neither page, the status is left untouched.
   */
  public function testXmlsitemapLinkAlterNoMatch(): void {
    $this->mockSiteConfig('/node/1', '/node/2');

    $link = ['loc' => '/node/3', 'status' => 1];
    $this->hooks->xmlsitemapLinkAlter($link, []);

    $this->assertSame(1, $link['status']);
  }

  /**
   * The base url from state is prepended to the entity's uri path on load.
   */
  public function testXmlsitemapLoadPrependsBaseUrl(): void {
    $this->state->method('get')
      ->with('xmlsitemap_base_url')
      ->willReturn('https://example.com');

    $entity1 = $this->createMock(XmlSitemapInterface::class);
    $entity1->method('get')->with('uri')->willReturn(['path' => '/node/1']);
    $entity1->expects($this->once())
      ->method('set')
      ->with('uri', ['path' => 'https://example.com/node/1']);

    $entity2 = $this->createMock(XmlSitemapInterface::class);
    $entity2->method('get')->with('uri')->willReturn(['path' => '/node/2']);
    $entity2->expects($this->once())
      ->method('set')
      ->with('uri', ['path' => 'https://example.com/node/2']);

    $this->hooks->xmlsitemapLoad([$entity1, $entity2]);
  }

  /**
   * An empty list of entities results in no calls to state or entities.
   */
  public function testXmlsitemapLoadWithNoEntities(): void {
    $this->state->expects($this->never())->method('get');
    $this->hooks->xmlsitemapLoad([]);
  }

}
