<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\Core\Form\FormState;
use Drupal\stanford_profile_helper\EventSubscriber\FormEventSubscriber;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityFormBuilderInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;

/**
 * Test the event subscriber.
 *
 * @coversDefaultClass \Drupal\stanford_profile_helper\EventSubscriber\FormEventSubscriber
 */
class FormEventSubscriberTest extends SuProfileHelperKernelTestBase {

  protected $paragraphType;
  /**
   * {@inheritDoc}
   */
  protected function setUp():void {
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
      FormEventSubscriber::argHelperAjaxCallback($form, $form_state);
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

      $paragraph_field_storage = FieldStorageConfig::create([
        'field_name' => 'su_spacer_size',
        'entity_type' => 'paragraph',
        'type' => 'list_string',
        'cardinality' => 1,
        'settings' => [
          'allowed_values' => [
            '_none' => '- None -',
            'option_2' => 'Option 2 Label',
          ],
        ],
      ])->save();

      //Maybe needs a cache clear?
      $entity_type_manager = \Drupal::service('entity_type.manager');
      $entity_type_manager->clearCachedDefinitions();

      FieldConfig::create([
        'field_storage' => $paragraph_field_storage,
        'bundle' => 'stanford_spacer',
        'settings' => [],
      ])->save();

      $paragraph = Paragraph::create([
        'type' => 'stanford_spacer',
        'su_spacer_size' => '_none',
      ]);
      $paragraph->save();

      $entity_form_builder = \Drupal::service('entity.form_builder');
      $form_object = $entity_form_builder->getForm($paragraph, 'default');

      $field_widget_structure = $form_object['su_spacer_size'];
      $this->assertEquals('Standard', $field_widget_structure['#options']['_none']);
    }

}
