<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_styles\Unit\Hook;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\stanford_profile_styles\Hook\ExtlinkHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for ExtlinkHooks.
 */
#[Group('stanford_profile_styles')]
#[CoversClass(ExtlinkHooks::class)]
class ExtlinkHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_styles\Hook\ExtlinkHooks
   */
  protected ExtlinkHooks $hooks;

  /**
   * Mock config_pages.loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $configPagesLoader;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->configPagesLoader = $this->createMock(ConfigPagesLoaderServiceInterface::class);

    $container = new ContainerBuilder();
    $container->set('config_pages.loader', $this->configPagesLoader);
    \Drupal::setContainer($container);

    $this->hooks = new ExtlinkHooks();
  }

  /**
   * Icons hidden setting is truthy — extlink_class is cleared.
   */
  public function testExtlinkSettingsAlterHidesIcons(): void {
    $this->configPagesLoader->method('getValue')
      ->with('stanford_basic_site_settings', 'su_hide_ext_link_icons', 0, 'value')
      ->willReturn(1);

    $settings = ['extlink_class' => 'ext-icon'];
    $this->hooks->extlinkSettingsAlter($settings);

    $this->assertSame('', $settings['extlink_class']);
  }

  /**
   * Icons hidden setting is falsy — extlink_class is left untouched.
   */
  public function testExtlinkSettingsAlterKeepsIcons(): void {
    $this->configPagesLoader->method('getValue')
      ->willReturn(0);

    $settings = ['extlink_class' => 'ext-icon'];
    $this->hooks->extlinkSettingsAlter($settings);

    $this->assertSame('ext-icon', $settings['extlink_class']);
  }

  /**
   * No extlink drupalSettings key present — nothing happens, no exception.
   */
  public function testPageAttachmentsAlterNoExtlinkKey(): void {
    $this->configPagesLoader->expects($this->never())->method('getValue');

    $attachments = ['#attached' => ['drupalSettings' => ['data' => []]]];
    $this->hooks->pageAttachmentsAlter($attachments);

    $this->assertArrayNotHasKey('extlink', $attachments['#attached']['drupalSettings']['data']);
  }

  /**
   * Extlink key present and icons should be hidden — class value is cleared.
   */
  public function testPageAttachmentsAlterHidesIcons(): void {
    $this->configPagesLoader->method('getValue')->willReturn(TRUE);

    $attachments = [
      '#attached' => [
        'drupalSettings' => [
          'data' => [
            'extlink' => ['extAdditionalLinkClasses' => 'ext-icon'],
          ],
        ],
      ],
    ];
    $this->hooks->pageAttachmentsAlter($attachments);

    $this->assertSame('', $attachments['#attached']['drupalSettings']['data']['extlink']['extAdditionalLinkClasses']);
  }

  /**
   * Extlink key present but icons should not be hidden — value untouched.
   */
  public function testPageAttachmentsAlterKeepsIcons(): void {
    $this->configPagesLoader->method('getValue')->willReturn(FALSE);

    $attachments = [
      '#attached' => [
        'drupalSettings' => [
          'data' => [
            'extlink' => ['extAdditionalLinkClasses' => 'ext-icon'],
          ],
        ],
      ],
    ];
    $this->hooks->pageAttachmentsAlter($attachments);

    $this->assertSame('ext-icon', $attachments['#attached']['drupalSettings']['data']['extlink']['extAdditionalLinkClasses']);
  }

}
