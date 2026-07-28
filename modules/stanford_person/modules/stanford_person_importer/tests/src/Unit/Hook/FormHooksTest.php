<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_person_importer\Unit\Hook;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_person_importer\Cap;
use Drupal\stanford_person_importer\Hook\FormHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for FormHooks.
 */
#[Group('stanford_person_importer')]
#[CoversClass(FormHooks::class)]
class FormHooksTest extends UnitTestCase {

  /**
   * The hooks class under test.
   *
   * @var \Drupal\stanford_person_importer\Hook\FormHooks
   */
  protected FormHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new FormHooks();
  }

  /**
   * Build a context array with the given field name.
   */
  protected function getContext(string $field_name): array {
    $field_definition = $this->createMock(FieldDefinitionInterface::class);
    $field_definition->method('getName')->willReturn($field_name);

    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getFieldDefinition')->willReturn($field_definition);

    return ['items' => $items];
  }

  /**
   * The CAP password field gets the credential validator attached.
   */
  public function testCapPasswordFieldGetsValidator() {
    $form_state = $this->createMock(FormStateInterface::class);
    $context = $this->getContext('su_person_cap_password');

    $field_widget_complete_form = ['widget' => [0 => []]];
    $this->hooks->fieldWidgetCompleteFormAlter($field_widget_complete_form, $form_state, $context);

    $this->assertSame(
      [[Cap::class, 'validateCredentials']],
      $field_widget_complete_form['widget'][0]['#element_validate']
    );
  }

  /**
   * Other fields are left untouched.
   */
  public function testOtherFieldNotAltered() {
    $form_state = $this->createMock(FormStateInterface::class);
    $context = $this->getContext('some_other_field');

    $field_widget_complete_form = ['widget' => [0 => []]];
    $this->hooks->fieldWidgetCompleteFormAlter($field_widget_complete_form, $form_state, $context);

    $this->assertArrayNotHasKey('#element_validate', $field_widget_complete_form['widget'][0]);
  }

}
