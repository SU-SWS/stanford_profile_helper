<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_intranet\Unit\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_intranet\Hook\FormHooks;
use Drupal\stanford_intranet\Plugin\Field\FieldType\EntityAccessFieldType;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for FormHooks.
 */
#[Group('stanford_intranet')]
class FormHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_intranet\Hook\FormHooks
   */
  protected FormHooks $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new FormHooks();
  }

  /**
   * The access field is moved into the revision information group.
   */
  public function testFormNodeFormAlterMovesFieldIntoGroup() {
    $form = [
      EntityAccessFieldType::FIELD_NAME => [
        '#type' => 'details',
      ],
    ];
    $form_state = $this->createMock(FormStateInterface::class);

    $this->hooks->formNodeFormAlter($form, $form_state, 'node_page_form');

    $this->assertSame(
      'revision_information',
      $form[EntityAccessFieldType::FIELD_NAME]['#group']
    );
  }

  /**
   * When the access field is not present, nothing is added to the form.
   */
  public function testFormNodeFormAlterNoFieldPresent() {
    $form = ['title' => ['#type' => 'textfield']];
    $form_state = $this->createMock(FormStateInterface::class);

    $this->hooks->formNodeFormAlter($form, $form_state, 'node_page_form');

    $this->assertArrayNotHasKey(EntityAccessFieldType::FIELD_NAME, $form);
    $this->assertSame(['title' => ['#type' => 'textfield']], $form);
  }

}
