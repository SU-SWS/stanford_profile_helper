<?php

declare(strict_types=1);

namespace Drupal\Tests\jumpstart_ui\Unit\Hook;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\jumpstart_ui\Hook\StatButtonHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for StatButtonHooks.
 */
#[Group('jumpstart_ui')]
#[CoversClass(StatButtonHooks::class)]
class StatButtonHooksTest extends UnitTestCase {

  /**
   * The hooks class under test.
   */
  protected StatButtonHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new StatButtonHooks();
  }

  /**
   * When the link style is 'action', the action class should be added.
   */
  public function testPreprocessFieldSuStatButtonWithActionStyle(): void {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('getString')->willReturn('action');

    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('get')
      ->with('su_stat_link_style')
      ->willReturn($field);

    $variables = [
      'element' => ['#object' => $entity],
      'items' => [
        0 => ['content' => ['#options' => []]],
      ],
    ];

    $this->hooks->preprocessFieldSuStatButton($variables);

    $this->assertSame(
      ['su-link--action'],
      $variables['items'][0]['content']['#options']['attributes']['class']
    );
  }

  /**
   * When the link style is not 'action', nothing should be altered.
   */
  public function testPreprocessFieldSuStatButtonWithOtherStyle(): void {
    $field = $this->createMock(FieldItemListInterface::class);
    $field->method('getString')->willReturn('link');

    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('get')
      ->with('su_stat_link_style')
      ->willReturn($field);

    $variables = [
      'element' => ['#object' => $entity],
      'items' => [
        0 => ['content' => ['#options' => []]],
      ],
    ];

    $this->hooks->preprocessFieldSuStatButton($variables);

    $this->assertArrayNotHasKey(
      'attributes',
      $variables['items'][0]['content']['#options']
    );
  }

  /**
   * When the field is empty (get() returns NULL), the nullsafe operator
   * should short circuit and nothing should be altered.
   */
  public function testPreprocessFieldSuStatButtonWithNoLinkStyleField(): void {
    $entity = $this->createMock(FieldableEntityInterface::class);
    $entity->method('get')
      ->with('su_stat_link_style')
      ->willReturn(NULL);

    $variables = [
      'element' => ['#object' => $entity],
      'items' => [
        0 => ['content' => ['#options' => []]],
      ],
    ];

    $this->hooks->preprocessFieldSuStatButton($variables);

    $this->assertArrayNotHasKey(
      'attributes',
      $variables['items'][0]['content']['#options']
    );
  }

  /**
   * When the field name is one of the color fields, the '#states' key
   * should be added to the form.
   */
  public function testFieldWidgetCompleteColorFieldWidgetBoxFormAlterWithIconColor(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('su_stat_icon_color');

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getFieldDefinition')->willReturn($field_definition);

    $form_state = $this->createMock(FormStateInterface::class);
    $field_widget_complete_form = [];
    $context = ['items' => $items];

    $this->hooks->fieldWidgetCompleteColorFieldWidgetBoxFormAlter(
      $field_widget_complete_form,
      $form_state,
      $context
    );

    $this->assertSame([
      'invisible' => [
        'input[name="su_stat_bg_color[0][color]"]' => ['filled' => TRUE],
      ],
    ], $field_widget_complete_form['#states']);
  }

  /**
   * The stat color field name should also trigger the '#states' addition.
   */
  public function testFieldWidgetCompleteColorFieldWidgetBoxFormAlterWithStatColor(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('su_stat_stat_color');

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getFieldDefinition')->willReturn($field_definition);

    $form_state = $this->createMock(FormStateInterface::class);
    $field_widget_complete_form = [];
    $context = ['items' => $items];

    $this->hooks->fieldWidgetCompleteColorFieldWidgetBoxFormAlter(
      $field_widget_complete_form,
      $form_state,
      $context
    );

    $this->assertArrayHasKey('#states', $field_widget_complete_form);
  }

  /**
   * A field name not in the color list should not trigger any changes.
   */
  public function testFieldWidgetCompleteColorFieldWidgetBoxFormAlterWithOtherField(): void {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn('su_stat_bg_color');

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getFieldDefinition')->willReturn($field_definition);

    $form_state = $this->createMock(FormStateInterface::class);
    $field_widget_complete_form = [];
    $context = ['items' => $items];

    $this->hooks->fieldWidgetCompleteColorFieldWidgetBoxFormAlter(
      $field_widget_complete_form,
      $form_state,
      $context
    );

    $this->assertArrayNotHasKey('#states', $field_widget_complete_form);
  }

}
