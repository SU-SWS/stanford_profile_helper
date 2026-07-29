<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\stanford_profile_helper\Hook\FormHooks;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\taxonomy\VocabularyListBuilder;
use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test the event subscriber.
 */
#[RunTestsInSeparateProcesses]
class FormHooksTest extends SuProfileHelperKernelTestBase {

  /**
   * A mock paragraph type.
   *
   * @var \Drupal\paragraphs\Entity\ParagraphsType
   */
  protected $paragraphType;

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->installEntitySchema('paragraph');
    $this->installSchema('file', ['file_usage']);
  }

  /**
   * Term form contains an arg helper field.
   */
  public function testTaxonomyFormAlter() {
    Vocabulary::create(['vid' => 'foobar', 'label' => 'Foo'])->save();
    $term = Term::create(['vid' => 'foobar', 'name' => 'foo bar & baz -bin _']);
    $term->save();
    $form = \Drupal::service('entity.form_builder')->getForm($term, 'default');
    $this->assertEquals('foobarbazbin', $form['name']['arg_helper']['#default_value']);

    $form_state = new FormState();
    $form_state->setValue(['name', 0, 'value'], 'bar_baz &$ bin foo');
    FormHooks::argHelperAjaxCallback($form, $form_state);
    $this->assertEquals('barbazbinfoo', $form['name']['arg_helper']['#value']);
  }

  /**
   * Test the label on the spacer paragraph.
   */
  public function testFieldWidgetFormAlter() {
    $this->paragraphType = ParagraphsType::create([
      'id' => 'stanford_spacer',
      'label' => 'Mock Spacer',
    ])->save();

    $this->container->get('state')->set('nobots', TRUE);
    $paragraph_field_storage = FieldStorageConfig::create([
      'field_name' => 'su_site_nobots',
      'entity_type' => 'paragraph',
      'type' => 'boolean',
    ]);
    $paragraph_field_storage->save();

    FieldConfig::create([
      'field_storage' => $paragraph_field_storage,
      'bundle' => 'stanford_spacer',
      'settings' => [],
    ])->save();

    $paragraph_field_storage = FieldStorageConfig::create([
      'field_name' => 'field_viewfield',
      'entity_type' => 'paragraph',
      'type' => 'viewfield',
    ]);
    $paragraph_field_storage->save();

    FieldConfig::create([
      'field_storage' => $paragraph_field_storage,
      'bundle' => 'stanford_spacer',
      'settings' => [],
    ])->save();

    $paragraph_field_storage = FieldStorageConfig::create([
      'field_name' => 'su_spacer_size',
      'entity_type' => 'paragraph',
      'type' => 'list_string',
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [
          'option_1' => 'Option 1',
          'option_2' => 'Option 2',
        ],
      ],
    ]);
    $paragraph_field_storage->save();

    FieldConfig::create([
      'field_storage' => $paragraph_field_storage,
      'bundle' => 'stanford_spacer',
      'settings' => [],
    ])->save();

    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'paragraph',
      'bundle' => 'stanford_spacer',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $form_display->setComponent('su_spacer_size', ['type' => 'options_select'])
      ->setComponent('su_site_nobots')
      ->setComponent('field_viewfield');
    $form_display->save();

    $paragraph = Paragraph::create([
      'type' => 'stanford_spacer',
      'su_spacer_size' => '_none',
    ]);
    $paragraph->save();

    $entity_type_manager = \Drupal::service('entity_type.manager');
    $entity_form_builder = \Drupal::service('entity.form_builder');

    // This creates the form array, but is not a form object.
    $complete_form_array = $entity_form_builder->getForm($paragraph);

    // We do need the form object.
    $form_object = $entity_type_manager->getFormObject($paragraph->getEntityTypeId(), 'default');
    $form_object->setEntity($paragraph);

    $form_state = new FormState();
    $form_state->setFormObject($form_object);
    $form_state->setCompleteForm($complete_form_array);

    $spacer_form_element = $complete_form_array['su_spacer_size'];

    $this->assertEquals('Standard', $spacer_form_element['widget']['#options']['_none']);
    $this->assertTrue($complete_form_array['su_site_nobots']['widget']['value']['#default_value']);
    $this->assertArrayNotHasKey('token_help', $complete_form_array['field_viewfield']['widget'][0]['view_options']);
  }

  public function testTaxonomyOverviewForm() {
    $entity_type_manager = $this->container->get('entity_type.manager');
    $entity_type = $entity_type_manager->getDefinition('taxonomy_vocabulary');

    $entity_type_manager->getStorage('taxonomy_vocabulary')->create([
      'vid' => 'foo',
      'name' => 'foo',
    ])->save();

    $listBuilder = VocabularyListBuilder::createInstance($this->container, $entity_type);
    $form = [];
    $form_state = new FormState();
    $form = $listBuilder->buildForm($form, $form_state);

    $this->assertArrayHasKey('#tabledrag', $form['vocabularies']);
    $this->assertArrayHasKey('foo', $form['vocabularies']);

    \Drupal::moduleHandler()
      ->alter('form_taxonomy_overview_vocabularies', $form, $form_state);
    $this->assertArrayNotHasKey('#tabledrag', $form['vocabularies']);
    $this->assertArrayNotHasKey('foo', $form['vocabularies']);
  }

  /**
   * Test the layout_selection field widget form alter for stanford_news.
   */
  public function testLayoutSelectionFieldWidgetFormAlter() {
    $this->installEntitySchema('node');
    $this->installConfig(['node', 'field']);
    
    $news_type = $this->container->get('entity_type.manager')
      ->getStorage('node_type')
      ->create([
        'type' => 'stanford_news',
        'name' => 'News',
      ]);
    $news_type->save();

    $layout_field_storage = FieldStorageConfig::create([
      'field_name' => 'layout_selection',
      'entity_type' => 'node',
      'type' => 'list_string',
      'cardinality' => 1,
      'settings' => [
        'allowed_values' => [
          'layout_1' => 'Layout 1',
          'layout_2' => 'Layout 2',
        ],
      ],
    ]);
    $layout_field_storage->save();

    FieldConfig::create([
      'field_storage' => $layout_field_storage,
      'bundle' => 'stanford_news',
      'label' => 'Layout Selection',
      'settings' => [],
    ])->save();

    $news_form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'stanford_news',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $news_form_display->setComponent('layout_selection', [
      'type' => 'options_select'
    ]);
    $news_form_display->save();

    $news_node = $this->container->get('entity_type.manager')
      ->getStorage('node')
      ->create([
        'type' => 'stanford_news',
        'title' => 'Test News',
      ]);
    $news_node->save();

    $form = \Drupal::service('entity.form_builder')->getForm($news_node);

    $this->assertEquals('News', $form['layout_selection']['widget']['#options']['_none']);
    $this->assertEquals('Variant', (string) $form['layout_selection']['widget']['#title']);
  }
}
