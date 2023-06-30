<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\Core\Form\FormState;
use Drupal\stanford_profile_helper\EventSubscriber\FormEventSubscriber;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;

/**
 * Test the event subscriber.
 *
 * @coversDefaultClass \Drupal\stanford_profile_helper\EventSubscriber\FormEventSubscriber
 */
class FormEventSubscriberTest extends SuProfileHelperKernelTestBase {

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

}
