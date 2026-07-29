<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\Plugin\search_api\processor;

use Drupal\search_api\Item\Field;
use Drupal\search_api\Utility\Utility;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Drupal\Tests\search_api\Kernel\Processor\CustomValueTest as SearchApiCustomValueTest;

/**
 */
#[RunTestsInSeparateProcesses]
class CustomValueTest extends SearchApiCustomValueTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'node',
    'field',
    'search_api',
    'search_api_db',
    'search_api_test',
    'comment',
    'text',
    'system',
    'stanford_profile_helper',
    'file',
    'rabbit_hole',
    'config_pages',
    'token_or',
  ];

  public function setUp($processor = NULL): void {
    parent::setUp($processor);

    $field = new Field($this->index, 'custom_value_token_or');
    $field->setType('string');
    $field->setPropertyPath('custom_value');
    $field->setLabel('Type/Author');
    $field->setConfiguration(['value' => '[foo:bar|node:type|node:title] [node:title|bar:foo]']);
    $this->index->addField($field);
  }

  public function testPlugin() {
    /** @var \Drupal\search_api\Processor\ProcessorPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.search_api.processor');
    $plugin = $plugin_manager->createInstance('custom_value');
    $this->assertInstanceOf('\Drupal\stanford_profile_helper\Plugin\search_api\processor\CustomValue', $plugin);
  }

  /**
   * Preventing warning from the invalid "covers" annotation.
   */
  public function testItemFieldExtraction() {
    parent::testItemFieldExtraction();

    $node = $this->entities['node'];
    $id = Utility::createCombinedId('entity:node', $node->id() . ':en');
    $item = \Drupal::getContainer()
      ->get('search_api.fields_helper')
      ->createItemFromObject($this->index, $node->getTypedData(), $id);

    // Extract field values and check the value of our field.
    $fields = $item->getFields();
    $expected = ['article Test'];
    $this->assertEquals($expected, $fields['custom_value_token_or']->getValues());
  }

}
