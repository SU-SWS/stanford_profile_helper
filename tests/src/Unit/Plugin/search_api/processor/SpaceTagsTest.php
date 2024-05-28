<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\search_api\processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\stanford_profile_helper\Plugin\search_api\processor\SpaceTags;
use Drupal\Tests\UnitTestCase;

class SpaceTagsTest extends UnitTestCase {

  /**
   * @var \Drupal\stanford_profile_helper\Plugin\search_api\processor\SpaceTags
   */
  protected $plugin;

  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('search_api.fields_helper', $this->createMock(FieldsHelperInterface::class));
    $container->set('search_api.data_type_helper', $this->createMock(DataTypeHelperInterface::class));
    $container->set('plugin.manager.element_info', $this->createMock(ElementInfoManagerInterface::class));

    $this->plugin = TestSpaceTags::create($container, [], '', []);
    $index = $this->createMock(IndexInterface::class);
    $index->method('getFields')->willReturn([]);
    $this->plugin->setIndex($index);
  }

  public function testFieldProcessor() {
    $this->assertTrue($this->plugin->defaultConfiguration()['all_fields']);

    $value = '<div>foo</div><div>bar</div>';
    $this->plugin->processFieldValue($value, '');
    $this->assertEquals('<div>foo</div> <div>bar</div>', $value);
  }

}

class TestSpaceTags extends SpaceTags {

  public function processFieldValue(&$value, $type) {
    return parent::processFieldValue($value, $type);
  }

}
