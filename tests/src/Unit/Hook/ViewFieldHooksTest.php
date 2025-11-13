<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_profile_helper\Hook\ViewFieldHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit test for ViewFieldHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(ViewFieldHooks::class)]
class ViewFieldHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\ViewFieldHooks
   */
  protected $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new ViewFieldHooks();
  }

  /**
   * Test that the form alter adds the library attachment.
   */
  public function testLibraryAttachment() {
    $field_widget_complete_form = [
      'widget' => [],
    ];
    $form_state = $this->createMock(FormStateInterface::class);
    $context = [];

    $this->hooks->viewfieldFormAlter(
      $field_widget_complete_form,
      $form_state,
      $context
    );

    $this->assertArrayHasKey('#attached', $field_widget_complete_form);
    $this->assertArrayHasKey('library', $field_widget_complete_form['#attached']);
    $this->assertContains('stanford_profile_helper/viewfield_autocomplete', $field_widget_complete_form['#attached']['library']);
  }

  /**
   * Test that the form alter adds autocomplete to widget deltas.
   */
  public function testAutocompleteRouteAddedToWidgets() {
    $field_widget_complete_form = [
      'widget' => [
        0 => [
          '#delta' => 0,
          'target_id' => ['#default_value' => 'stanford_news'],
          'display_id' => ['#default_value' => 'block_1'],
          'view_options' => [
            'arguments' => [],
          ],
        ],
        1 => [
          '#delta' => 1,
          'target_id' => ['#default_value' => 'stanford_events'],
          'display_id' => ['#default_value' => 'default'],
          'view_options' => [
            'arguments' => [],
          ],
        ],
      ],
    ];
    $form_state = $this->createMock(FormStateInterface::class);
    $context = [];

    $this->hooks->viewfieldFormAlter(
      $field_widget_complete_form,
      $form_state,
      $context
    );

    // Check first delta.
    $this->assertEquals(
      'stanford_profile_helper.autocomplete.viewfield',
      $field_widget_complete_form['widget'][0]['view_options']['arguments']['#autocomplete_route_name']
    );
    $this->assertEquals(
      'stanford_news',
      $field_widget_complete_form['widget'][0]['view_options']['arguments']['#autocomplete_route_parameters']['view']
    );
    $this->assertEquals(
      'block_1',
      $field_widget_complete_form['widget'][0]['view_options']['arguments']['#autocomplete_route_parameters']['display']
    );

    // Check second delta.
    $this->assertEquals(
      'stanford_profile_helper.autocomplete.viewfield',
      $field_widget_complete_form['widget'][1]['view_options']['arguments']['#autocomplete_route_name']
    );
    $this->assertEquals(
      'stanford_events',
      $field_widget_complete_form['widget'][1]['view_options']['arguments']['#autocomplete_route_parameters']['view']
    );
    $this->assertEquals(
      'default',
      $field_widget_complete_form['widget'][1]['view_options']['arguments']['#autocomplete_route_parameters']['display']
    );
  }

  /**
   * Test that the form alter adds the viewfield-autocomplete class.
   */
  public function testViewfieldAutocompleteClassAdded() {
    $field_widget_complete_form = [
      'widget' => [
        0 => [
          '#delta' => 0,
          'target_id' => ['#default_value' => 'test_view'],
          'display_id' => ['#default_value' => 'default'],
          'view_options' => [
            'arguments' => [],
          ],
        ],
      ],
    ];
    $form_state = $this->createMock(FormStateInterface::class);
    $context = [];

    $this->hooks->viewfieldFormAlter(
      $field_widget_complete_form,
      $form_state,
      $context
    );

    $this->assertArrayHasKey('#attributes', $field_widget_complete_form['widget'][0]);
    $this->assertArrayHasKey('class', $field_widget_complete_form['widget'][0]['#attributes']);
    $this->assertContains('viewfield-autocomplete', $field_widget_complete_form['widget'][0]['#attributes']['class']);
  }

  /**
   * Test handling of missing default values.
   */
  public function testMissingDefaultValues() {
    $field_widget_complete_form = [
      'widget' => [
        0 => [
          '#delta' => 0,
          'target_id' => ['#default_value' => NULL],
          'display_id' => ['#default_value' => NULL],
          'view_options' => [
            'arguments' => [],
          ],
        ],
      ],
    ];
    $form_state = $this->createMock(FormStateInterface::class);
    $context = [];

    $this->hooks->viewfieldFormAlter(
      $field_widget_complete_form,
      $form_state,
      $context
    );

    $this->assertEquals(
      'none',
      $field_widget_complete_form['widget'][0]['view_options']['arguments']['#autocomplete_route_parameters']['view']
    );
    $this->assertEquals(
      'none',
      $field_widget_complete_form['widget'][0]['view_options']['arguments']['#autocomplete_route_parameters']['display']
    );
  }

  /**
   * Test handling of empty default values.
   */
  public function testEmptyDefaultValues() {
    $field_widget_complete_form = [
      'widget' => [
        0 => [
          '#delta' => 0,
          'target_id' => ['#default_value' => ''],
          'display_id' => ['#default_value' => ''],
          'view_options' => [
            'arguments' => [],
          ],
        ],
      ],
    ];
    $form_state = $this->createMock(FormStateInterface::class);
    $context = [];

    $this->hooks->viewfieldFormAlter(
      $field_widget_complete_form,
      $form_state,
      $context
    );

    $this->assertEquals(
      'none',
      $field_widget_complete_form['widget'][0]['view_options']['arguments']['#autocomplete_route_parameters']['view']
    );
    $this->assertEquals(
      'none',
      $field_widget_complete_form['widget'][0]['view_options']['arguments']['#autocomplete_route_parameters']['display']
    );
  }

  /**
   * Test form with no widget children.
   */
  public function testNoWidgetChildren() {
    $field_widget_complete_form = [
      'widget' => [],
    ];
    $form_state = $this->createMock(FormStateInterface::class);
    $context = [];

    $this->hooks->viewfieldFormAlter(
      $field_widget_complete_form,
      $form_state,
      $context
    );

    $this->assertArrayHasKey('#attached', $field_widget_complete_form);
    $this->assertContains('stanford_profile_helper/viewfield_autocomplete', $field_widget_complete_form['#attached']['library']);
    $this->assertEmpty(array_filter(array_keys($field_widget_complete_form['widget']), 'is_numeric'));
  }

  /**
   * Test multiple widget deltas are processed correctly.
   */
  public function testMultipleDeltas() {
    $field_widget_complete_form = [
      'widget' => [
        0 => [
          '#delta' => 0,
          'target_id' => ['#default_value' => 'view_one'],
          'display_id' => ['#default_value' => 'display_one'],
          'view_options' => ['arguments' => []],
        ],
        1 => [
          '#delta' => 1,
          'target_id' => ['#default_value' => 'view_two'],
          'display_id' => ['#default_value' => 'display_two'],
          'view_options' => ['arguments' => []],
        ],
        2 => [
          '#delta' => 2,
          'target_id' => ['#default_value' => 'view_three'],
          'display_id' => ['#default_value' => 'display_three'],
          'view_options' => ['arguments' => []],
        ],
      ],
    ];
    $form_state = $this->createMock(FormStateInterface::class);
    $context = [];

    $this->hooks->viewfieldFormAlter(
      $field_widget_complete_form,
      $form_state,
      $context
    );

    for ($i = 0; $i < 3; $i++) {
      $this->assertContains('viewfield-autocomplete', $field_widget_complete_form['widget'][$i]['#attributes']['class']);
      $this->assertEquals(
        'stanford_profile_helper.autocomplete.viewfield',
        $field_widget_complete_form['widget'][$i]['view_options']['arguments']['#autocomplete_route_name']
      );
    }

    $this->assertEquals('view_one', $field_widget_complete_form['widget'][0]['view_options']['arguments']['#autocomplete_route_parameters']['view']);
    $this->assertEquals('view_two', $field_widget_complete_form['widget'][1]['view_options']['arguments']['#autocomplete_route_parameters']['view']);
    $this->assertEquals('view_three', $field_widget_complete_form['widget'][2]['view_options']['arguments']['#autocomplete_route_parameters']['view']);
  }

}
