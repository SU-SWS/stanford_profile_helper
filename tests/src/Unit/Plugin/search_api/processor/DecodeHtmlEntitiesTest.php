<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\Plugin\search_api\processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\stanford_profile_helper\Plugin\search_api\processor\DecodeHtmlEntities;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the decode HTML entities search API processor.
 */
#[Group('stanford_profile_helper')]
class DecodeHtmlEntitiesTest extends UnitTestCase {

  /**
   * The processor plugin being tested.
   *
   * @var \Drupal\stanford_profile_helper\Plugin\search_api\processor\DecodeHtmlEntities
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('search_api.fields_helper', $this->createMock(FieldsHelperInterface::class));
    $container->set('search_api.data_type_helper', $this->createMock(DataTypeHelperInterface::class));
    $container->set('plugin.manager.element_info', $this->createMock(ElementInfoManagerInterface::class));

    $this->plugin = TestDecodeHtmlEntities::create($container, [], '', []);
    $index = $this->createMock(IndexInterface::class);
    $index->method('getFields')->willReturn([]);
    $this->plugin->setIndex($index);
  }

  /**
   * Test HTML entities are decoded in strings and arrays.
   */
  public function testFieldProcessor() {
    $string = htmlentities('<div>foobar</div>');
    $this->plugin->processFieldValue($string, 'text');
    $this->assertEquals($string, '<div>foobar</div>');

    $arry = [$string];
    $this->plugin->processFieldValue($arry, 'text');
    $this->assertEquals([$string], $arry);
  }

}

/**
 * Decode HTML entities processor that exposes processFieldValue().
 */
class TestDecodeHtmlEntities extends DecodeHtmlEntities {

  /**
   * Expose the protected method so the test can call it.
   */
  public function processFieldValue(&$value, $type) {
    return parent::processFieldValue($value, $type);
  }

}
