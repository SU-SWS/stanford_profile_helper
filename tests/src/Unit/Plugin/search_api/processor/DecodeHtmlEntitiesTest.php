<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\search_api\processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\stanford_profile_helper\Plugin\search_api\processor\DecodeHtmlEntities;
use Drupal\Tests\UnitTestCase;

class DecodeHtmlEntitiesTest extends UnitTestCase {

  /**
   * @var \Drupal\stanford_profile_helper\Plugin\search_api\processor\DecodeHtmlEntities
   */
  protected $plugin;

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

  public function testFieldProcessor() {
    $string = htmlentities('<div>foobar</div>');
    $this->plugin->processFieldValue($string, 'text');
    $this->assertEquals($string, '<div>foobar</div>');

    $arry = [$string];
    $this->plugin->processFieldValue($arry, 'text');
    $this->assertEquals([$string], $arry);
  }

}

class TestDecodeHtmlEntities extends DecodeHtmlEntities {

  public function processFieldValue(&$value, $type) {
    return parent::processFieldValue($value, $type);
  }

}
