<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\Config;

use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Config\Config;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\State\StateInterface;
use Drupal\stanford_profile_helper\Config\ConfigOverrides;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\config_pages\ConfigPagesInterface;
use Drupal\config_pages\Entity\ConfigPages;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\file\Entity\File;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;
use Drupal\Core\StreamWrapper\StreamWrapperInterface;

/**
 * Class ConfigOverridesTest
 *
 * @group stanford_profile_helper
 * @coversDefaultClass \Drupal\stanford_profile_helper\Config\ConfigOverrides
 */
class ConfigOverridesTest extends UnitTestCase {

  /**
   * @var \Drupal\stanford_profile_helper_helper\Config\ConfigOverrides
   */
  protected $overrideService;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->will($this->returnCallback([
      $this,
      'getStateCallback',
    ]));

    $config_pages = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $entity_manager = $this->createMock(EntityTypeManagerInterface::class);
    $stream_wrapper_manager = $this->createMock(StreamWrapperManagerInterface::class);

    $config_factory->method('getEditable')
      ->will($this->returnCallback([$this, 'getConfigCallback']));

    $this->overrideService = new ConfigOverrides($state, $config_pages, $config_factory, $entity_manager, $stream_wrapper_manager);
  }

  /**
   * Lockup custom overrides through config form.
   */
  public function testConfigLockupOverrides() {

    $state = $this->createMock(StateInterface::class);
    $state->method('get')->will($this->returnCallback([
      $this,
      'getStateCallback',
    ]));

    $config_pages = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $config_factory = $this->createMock(ConfigFactoryInterface::class);
    $entity_manager = $this->createMock(EntityTypeManagerInterface::class);
    $file = $this->createMock(File::class);
    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $stream_wrapper_manager = $this->createMock(StreamWrapperManagerInterface::class);
    $stream_wrapper = $this->createMock(StreamWrapperInterface::class);

    $config_factory->method('getEditable')->will(
      $this->returnCallback([$this, 'getConfigCallback'])
    );

    $config_factory->method('get')->will(
      $this->returnCallback([$this, 'getConfigCallback'])
    );

    $config_pages->method('load')->will(
      $this->returnCallback([$this, 'getConfigPageLockup'])
    );

    $file->method('getFileUri')->willReturn('public://logo.jpg');
    $entity_storage->method('load')->willReturn($file);
    $entity_manager->method('getStorage')->willReturn($entity_storage);

    $stream_wrapper->method('getExternalUrl')->willReturn('/sites/default/files/logo.jpg');
    $stream_wrapper_manager->method('getViaUri')->willReturn($stream_wrapper);

    $overrideService = new ConfigOverrides($state, $config_pages, $config_factory, $entity_manager, $stream_wrapper_manager);

    $overrides = $overrideService->loadOverrides(['stanford_basic.settings']);
    $expected = ['stanford_basic.settings' =>
      [
        'lockup' => [
          'option' => 'a',
          'line1' => 'Line 1',
          'line2' => 'Line 2',
          'line3' => 'Line 3',
          'line4' => 'Line 4',
          'line5' => 'Line 5',
        ],
        'logo' => [
          'path' => '/sites/default/files/logo.jpg',
          'use_default' => TRUE,
        ],
      ],
    ];
    $this->assertArrayEquals($expected, $overrides);

    // TEST SOME FAILURES FOR MORE COVERAGE.
    // -------------------------------------------------------------------------

    // Test to avoid circular ref.
    $overrides = $overrideService->loadOverrides(['system.theme']);

    // Test a failed get image from config page.
    $config_page = $this->createMock(ConfigPages::class);
    $config_page->method('get')->willReturn(FALSE);
    $config_pages->method('load')->willReturn($config_page);
    $overrideService = new ConfigOverrides($state, $config_pages, $config_factory, $entity_manager, $stream_wrapper_manager);
    $overrides = $overrideService->loadOverrides(['stanford_basic.settings']);
    $this->assertTrue(is_array($overrides));

    // Test a failed get image fid.
    $obj = $this->createMock(FieldItemListInterface::class);
    $obj->method('getValue')->willReturn(FALSE);
    $obj->method('first')->will(
      $this->returnSelf()
    );
    $config_page = $this->createMock(ConfigPages::class);
    $config_page->method('get')->willReturn($obj);
    $config_pages->method('load')->willReturn($config_page);
    $overrideService = new ConfigOverrides($state, $config_pages, $config_factory, $entity_manager, $stream_wrapper_manager);
    $overrides = $overrideService->loadOverrides(['stanford_basic.settings']);
    $this->assertTrue(is_array($overrides));

    // Test a failed config page load.
    $config_pages->method('load')->willReturn(FALSE);
    $overrideService = new ConfigOverrides($state, $config_pages, $config_factory, $entity_manager, $stream_wrapper_manager);
    $overrides = $overrideService->loadOverrides(['stanford_basic.settings']);
    $this->assertTrue(is_array($overrides));

  }

  /**
   * [getConfigCallback description]
   * @param  [type] $name [description]
   * @return [type]       [description]
   */
  public function getConfigCallback($name) {
    $config = $this->createMock(Config::class);
    $setting = [];
    switch ($name) {
      case 'core.extension':
        $setting = ['stable' => 0, 'seven' => 0];
        break;

      case 'system.theme':
        $setting = ['default' => 'stanford_basic'];
        break;
    }

    $config->method('getOriginal')->willReturn($setting);
    $config->method('get')->with('default')->willReturn('stanford_basic');
    return $config;
  }

  /**
   * [getConfigPageLockup description]
   * @return [type] [description]
   */
  function getConfigPageLockup($name) {
    $config_page = $this->createMock(ConfigPages::class);

    $config_page->method('get')->will(
      $this->returnCallback([$this, 'getFieldValue'])
    );

    return $config_page;
  }

  /**
   * [getFieldValue description]
   * @param  [type] $name [description]
   * @return [type]       [description]
   */
  public function getFieldValue($name) {
    $obj = $this->createMock(FieldItemListInterface::class);

    switch ($name) {
      case 'su_lockup_options':
        $obj->method('getString')->willReturn('a');
        break;

      case 'su_line_1':
        $obj->method('getString')->willReturn('Line 1');
        break;

      case 'su_line_2':
        $obj->method('getString')->willReturn('Line 2');
        break;

      case 'su_line_3':
        $obj->method('getString')->willReturn('Line 3');
        break;

      case 'su_line_4':
        $obj->method('getString')->willReturn('Line 4');
        break;

      case 'su_line_5':
        $obj->method('getString')->willReturn('Line 5');
        break;

      case 'su_use_theme_logo':
        $obj->method('getString')->willReturn('1');
        break;

      case 'su_upload_logo_image':
        $obj->method('getValue')->willReturn([['target_id' => 1]]);
        break;
    }

    return $obj;
  }

  /**
   * [testCreateConfigObject description]
   * @return [type] [description]
   */
  function testDefaultFunctions() {
    $this->assertNull($this->overrideService->createConfigObject('name'));
    $this->assertEquals($this->overrideService->getCacheSuffix(), 'StanfordProfileHelperConfigOverride');
    $obj = new CacheableMetadata();
    $this->assertEquals(get_class($obj), get_class($this->overrideService->getCacheableMetadata('name')));
  }

}
