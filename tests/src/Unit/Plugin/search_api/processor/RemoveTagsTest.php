<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\search_api\processor;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Utility\DataTypeHelperInterface;
use Drupal\search_api\Utility\FieldsHelperInterface;
use Drupal\stanford_profile_helper\Plugin\search_api\processor\RemoveTags;
use Drupal\Tests\UnitTestCase;

class RemoveTagsTest extends UnitTestCase {

  /**
   * @var \Drupal\stanford_profile_helper\Plugin\search_api\processor\RemoveTags
   */
  protected $plugin;

  protected function setUp(): void {
    parent::setUp();
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('search_api.fields_helper', $this->createMock(FieldsHelperInterface::class));
    $container->set('search_api.data_type_helper', $this->createMock(DataTypeHelperInterface::class));
    $container->set('plugin.manager.element_info', $this->createMock(ElementInfoManagerInterface::class));

    $this->plugin = TestRemoveTags::create($container, [], '', []);
    $index = $this->createMock(IndexInterface::class);
    $index->method('getFields')->willReturn([]);
    $this->plugin->setIndex($index);
  }

  public function testFieldProcessorForm() {
    $this->assertContains('div', $this->plugin->defaultConfiguration()['tags']);

    $form = [];
    $form_state = new FormState();
    $form = $this->plugin->buildConfigurationForm($form, $form_state);
    $this->assertArrayHasKey('tags', $form);
    $form['tags']['#parents'] = [];

    $form_state->setValue('tags', "foo\n bar \nbaz");
    $this->plugin->validateConfigurationForm($form, $form_state);
    $this->assertEquals(['foo', 'bar', 'baz'], $form_state->getValue(['tags']));
    $this->assertFalse($form_state::hasAnyErrors());

    $form_state->setValue('tags', "foo bar\n baz");
    $this->plugin->validateConfigurationForm($form, $form_state);
    $this->assertTrue($form_state::hasAnyErrors());
  }

  public function testFieldProcessor() {
    $value = '<div>foo bar<p>baz</p></div>';
    $this->plugin->processFieldValue($value, '');
    $this->assertEquals('foo bar<p>baz</p>', $value);
  }

}

class TestRemoveTags extends RemoveTags {

  public function processFieldValue(&$value, $type) {
    return parent::processFieldValue($value, $type);
  }

}
