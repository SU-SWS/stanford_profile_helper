<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events\Unit\Hook;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\stanford_events\Hook\FormHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for FormHooks.
 */
#[Group('stanford_events')]
class FormHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_events\Hook\FormHooks
   */
  protected FormHooks $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new FormHooks();

    // t() calls produce TranslatableMarkup objects. Casting those to a
    // string (as several assertions below do) requires a string
    // translation service on the container.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Other field names should leave the table and rows untouched.
   */
  public function testPreprocessFieldMultipleValueFormOtherFieldName(): void {
    $variables = [
      'element' => [
        '#field_name' => 'some_other_field',
      ],
      'table' => [
        '#header' => ['foo'],
        '#tabledrag' => ['bar'],
        '#rows' => [],
      ],
      'button' => [],
    ];

    $this->hooks->preprocessFieldMultipleValueForm($variables);

    $this->assertArrayHasKey('#header', $variables['table']);
    $this->assertArrayHasKey('#tabledrag', $variables['table']);
  }

  /**
   * The 'su_event_schedule' field removes the table drag and order features.
   *
   * Drag handles and order columns are stripped from table rows and the
   * header/tabledrag keys are removed altogether.
   */
  public function testPreprocessFieldMultipleValueFormEventSchedule(): void {
    $variables = [
      'element' => [
        '#field_name' => 'su_event_schedule',
      ],
      'table' => [
        '#header' => ['foo'],
        '#tabledrag' => ['bar'],
        '#rows' => [
          [
            'data' => [
              'drag' => ['class' => ['field-multiple-drag']],
              'order' => ['class' => ['delta-order']],
              'value' => ['class' => ['some-other-class']],
              'empty_class' => ['class' => []],
              'no_class' => ['data' => 'value'],
            ],
          ],
        ],
      ],
      'button' => [],
    ];

    $this->hooks->preprocessFieldMultipleValueForm($variables);

    $this->assertArrayNotHasKey('#header', $variables['table']);
    $this->assertArrayNotHasKey('#tabledrag', $variables['table']);

    $row = $variables['table']['#rows'][0];
    $this->assertArrayNotHasKey('drag', $row['data']);
    $this->assertArrayNotHasKey('order', $row['data']);
    $this->assertArrayHasKey('value', $row['data']);
    $this->assertArrayHasKey('empty_class', $row['data']);
    $this->assertArrayHasKey('no_class', $row['data']);
  }

  /**
   * A child with the person CTA add-more button gets its value overridden.
   *
   * This happens even when the field name is not 'su_schedule_speaker'.
   */
  public function testPreprocessFieldMultipleValueFormChildWithPersonCta(): void {
    $variables = [
      'element' => [
        '#field_name' => 'some_other_field',
        0 => [
          'add_more_button_stanford_person_cta' => [
            '#value' => 'Add more',
          ],
        ],
      ],
      'button' => [],
    ];

    $this->hooks->preprocessFieldMultipleValueForm($variables);

    $this->assertEquals(
      'Add another speaker',
      (string) $variables['element'][0]['add_more_button_stanford_person_cta']['#value']
    );
    $this->assertArrayNotHasKey('add_more', $variables['element']);
  }

  /**
   * A child without the person CTA add-more button is left untouched.
   *
   * The field name here is not 'su_schedule_speaker'.
   */
  public function testPreprocessFieldMultipleValueFormChildWithoutPersonCta(): void {
    $variables = [
      'element' => [
        '#field_name' => 'some_other_field',
        0 => [
          '#type' => 'container',
        ],
      ],
      'button' => [],
    ];

    $this->hooks->preprocessFieldMultipleValueForm($variables);

    $this->assertArrayNotHasKey('add_more_button_stanford_person_cta', $variables['element'][0]);
    $this->assertArrayNotHasKey('add_more', $variables['element']);
  }

  /**
   * The 'su_schedule_speaker' field updates all the add-more buttons.
   *
   * Both the element and button render arrays are updated, for each child.
   */
  public function testPreprocessFieldMultipleValueFormScheduleSpeaker(): void {
    $variables = [
      'element' => [
        '#field_name' => 'su_schedule_speaker',
        0 => [
          '#type' => 'container',
        ],
      ],
      'button' => [],
    ];

    $this->hooks->preprocessFieldMultipleValueForm($variables);

    $this->assertEquals('Add another speaker', (string) $variables['element']['add_more']['#value']);
    $this->assertEquals('Add another speaker', (string) $variables['element']['add_more']['add_more_button_stanford_person_cta']['#value']);
    $this->assertEquals('Add another speaker', (string) $variables['element']['add_more_button_stanford_person_cta']['#value']);
    $this->assertEquals('Add another speaker', (string) $variables['button']['add_more']['#value']);
    $this->assertEquals('Add another speaker', (string) $variables['button']['add_more_button_stanford_person_cta']['add_more']['#value']);
    $this->assertEquals('Add another speaker', (string) $variables['button']['add_more_button_stanford_person_cta']['#value']);
  }

  /**
   * The person CTA key and the speaker field name trigger both branches.
   */
  public function testPreprocessFieldMultipleValueFormScheduleSpeakerWithPersonCta(): void {
    $variables = [
      'element' => [
        '#field_name' => 'su_schedule_speaker',
        0 => [
          'add_more_button_stanford_person_cta' => [
            '#value' => 'Add more',
          ],
        ],
      ],
      'button' => [],
    ];

    $this->hooks->preprocessFieldMultipleValueForm($variables);

    $this->assertEquals(
      'Add another speaker',
      (string) $variables['element'][0]['add_more_button_stanford_person_cta']['#value']
    );
    $this->assertEquals('Add another speaker', (string) $variables['element']['add_more']['#value']);
    $this->assertEquals('Add another speaker', (string) $variables['button']['add_more']['#value']);
  }

  /**
   * No children on the element means the second loop never runs its body.
   */
  public function testPreprocessFieldMultipleValueFormNoChildren(): void {
    $variables = [
      'element' => [
        '#field_name' => 'su_schedule_speaker',
      ],
      'button' => [],
    ];

    $this->hooks->preprocessFieldMultipleValueForm($variables);

    $this->assertArrayNotHasKey('add_more', $variables['element']);
    $this->assertArrayNotHasKey('add_more', $variables['button']);
  }

}
