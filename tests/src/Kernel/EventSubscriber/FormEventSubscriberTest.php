<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\stanford_profile_helper\EventSubscriber\FormEventSubscriber;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\field_event_dispatcher\Event\Field\WidgetCompleteFormAlterEvent;
use Drupal\field_event_dispatcher\FieldHookEvents;

/**
 * Test the event subscriber.
 *
 * @coversDefaultClass \Drupal\stanford_profile_helper\EventSubscriber\FormEventSubscriber
 */
class FormEventSubscriberTest extends SuProfileHelperKernelTestBase {

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
    $form_display->setComponent('su_spacer_size', ['type' => 'options_select']);
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

    $field_item = $paragraph->get('su_spacer_size');

    $form_state = new FormState();
    $form_state->setFormObject($form_object);
    $form_state->setCompleteForm($complete_form_array);

    $spacer_form_element = $complete_form_array['su_spacer_size'];

    $entity_form_display = EntityFormDisplay::collectRenderDisplay($form_state->getFormObject()->getEntity(), 'default');
    $widget = $entity_form_display->getRenderer('su_spacer_size');

    $context = [
      'form' => $form_object,
      'widget' => $widget,
      'items' => $field_item,
      'delta' => 0,
      'default' => FALSE,
    ];

    // Trigger the event.
    $event = new WidgetCompleteFormAlterEvent($spacer_form_element, $form_state, $context);
    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($event, FieldHookEvents::WIDGET_COMPLETE_FORM_ALTER);

    $this->assertEquals('Standard', $spacer_form_element['widget']['#options']['_none']);
  }

}
