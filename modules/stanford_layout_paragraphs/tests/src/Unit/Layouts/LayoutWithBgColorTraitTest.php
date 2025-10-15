<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_layout_paragraphs\Unit\Layouts;

use Drupal\Core\Form\FormState;
use Drupal\stanford_layout_paragraphs\Layouts\LayoutWithBgColorTrait;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for LayoutWithBgColorTrait.
 */
#[CoversClass(LayoutWithBgColorTrait::class)]
class LayoutWithBgColorTraitTest extends UnitTestCase {

  /**
   * The trait instance.
   *
   * @var object
   */
  protected $traitObject;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create an anonymous class that uses the trait.
    $this->traitObject = new class {
      use LayoutWithBgColorTrait;

      public $configuration = [];

      public function getPluginId() {
        return 'test_plugin';
      }

      public function t($string, array $args = [], array $options = []) {
        return new class($string) {

          public function __construct(private $string) {}

          public function render() {
            return $this->string;
          }

          public function __toString() {
            return $this->string;
          }

        };
      }

      public function addBackgroundColorElementPublic(array &$form, $form_state) {
        return $this->addBackgroundColorElement($form, $form_state);
      }

      public function addPaddingMarginElementsPublic(array &$form, $form_state) {
        return $this->addPaddingMarginElements($form, $form_state);
      }

      public function submitBackgroundFormPublic(array &$form, $form_state) {
        return $this->submitBackgroundForm($form, $form_state);
      }

      public function submitPaddingMarginFormPublic(array &$form, $form_state) {
        return $this->submitPaddingMarginForm($form, $form_state);
      }

    };
  }

  /**
   * Test addBackgroundColorElement adds form element.
   *
   * // Covers: addBackgroundColorElement
   */
  public function testAddBackgroundColorElement(): void {
    $form = [];
    $form_state = new FormState();

    $result = $this->traitObject->addBackgroundColorElementPublic($form, $form_state);

    $this->assertArrayHasKey('bg_color', $result);
    $this->assertEquals('textfield', $result['bg_color']['#type']);
    $this->assertEquals('Background Color', $result['bg_color']['#title']->render());
    $this->assertEquals(7, $result['bg_color']['#maxlength']);
    $this->assertEquals(7, $result['bg_color']['#size']);
  }

  /**
   * Test addBackgroundColorElement with existing color.
   *
   * // Covers: addBackgroundColorElement
   */
  public function testAddBackgroundColorElementWithExistingColor(): void {
    $this->traitObject->configuration['bg_color'] = 'ff0000';

    $form = [];
    $form_state = new FormState();

    $result = $this->traitObject->addBackgroundColorElementPublic($form, $form_state);

    $this->assertEquals('#ff0000', $result['bg_color']['#default_value']);
  }

  /**
   * Test addBackgroundColorElement without existing color.
   *
   * // Covers: addBackgroundColorElement
   */
  public function testAddBackgroundColorElementWithoutColor(): void {
    $form = [];
    $form_state = new FormState();

    $result = $this->traitObject->addBackgroundColorElementPublic($form, $form_state);

    $this->assertEquals('', $result['bg_color']['#default_value']);
  }

  /**
   * Test addBackgroundColorElement includes color library.
   *
   * // Covers: addBackgroundColorElement
   */
  public function testAddBackgroundColorElementLibrary(): void {
    $form = [];
    $form_state = new FormState();

    $result = $this->traitObject->addBackgroundColorElementPublic($form, $form_state);

    $this->assertArrayHasKey('#attached', $result['bg_color']);
    $this->assertArrayHasKey('library', $result['bg_color']['#attached']);
    $this->assertContains('color_field/color-field-widget-box', $result['bg_color']['#attached']['library']);
  }

  /**
   * Test addBackgroundColorElement includes drupalSettings.
   *
   * // Covers: addBackgroundColorElement
   */
  public function testAddBackgroundColorElementSettings(): void {
    $form = [];
    $form_state = new FormState();

    $result = $this->traitObject->addBackgroundColorElementPublic($form, $form_state);

    $this->assertArrayHasKey('drupalSettings', $result['bg_color']['#attached']);
    $this->assertArrayHasKey('color_field', $result['bg_color']['#attached']['drupalSettings']);
    $this->assertArrayHasKey('color_field_widget_box', $result['bg_color']['#attached']['drupalSettings']['color_field']);
  }

  /**
   * Test addBackgroundColorElement palette settings.
   *
   * // Covers: addBackgroundColorElement
   */
  public function testAddBackgroundColorElementPalette(): void {
    $form = [];
    $form_state = new FormState();

    $result = $this->traitObject->addBackgroundColorElementPublic($form, $form_state);

    $settings = $result['bg_color']['#attached']['drupalSettings']['color_field']['color_field_widget_box']['settings'];
    $this->assertIsArray($settings);

    // Get the first (and only) item from settings.
    $settingsItem = reset($settings);
    $this->assertArrayHasKey('palette', $settingsItem);
    $this->assertContains('#f4f4f4', $settingsItem['palette']);
    $this->assertContains('#ebeae5', $settingsItem['palette']);
    $this->assertFalse($settingsItem['required']);
  }

  /**
   * Test addPaddingMarginElements adds all form elements.
   *
   * // Covers: addPaddingMarginElements
   */
  public function testAddPaddingMarginElements(): void {
    $form = [];
    $form_state = new FormState();

    $result = $this->traitObject->addPaddingMarginElementsPublic($form, $form_state);

    $this->assertArrayHasKey('top_padding', $result);
    $this->assertArrayHasKey('bottom_padding', $result);
    $this->assertArrayHasKey('bottom_margin', $result);
  }

  /**
   * Test addPaddingMarginElements top_padding configuration.
   *
   * // Covers: addPaddingMarginElements
   */
  public function testAddPaddingMarginElementsTopPadding(): void {
    $form = [];
    $form_state = new FormState();

    $result = $this->traitObject->addPaddingMarginElementsPublic($form, $form_state);

    $this->assertEquals('select', $result['top_padding']['#type']);
    $this->assertEquals('Space inside the section - Top', $result['top_padding']['#title']->render());
    $this->assertArrayHasKey('#options', $result['top_padding']);
    $this->assertArrayHasKey('none', $result['top_padding']['#options']);
    $this->assertArrayHasKey('more', $result['top_padding']['#options']);
  }

  /**
   * Test addPaddingMarginElements bottom_padding configuration.
   *
   * // Covers: addPaddingMarginElements
   */
  public function testAddPaddingMarginElementsBottomPadding(): void {
    $form = [];
    $form_state = new FormState();

    $result = $this->traitObject->addPaddingMarginElementsPublic($form, $form_state);

    $this->assertEquals('select', $result['bottom_padding']['#type']);
    $this->assertEquals('Space inside the section - Bottom', $result['bottom_padding']['#title']->render());
    $this->assertArrayHasKey('#options', $result['bottom_padding']);
    $this->assertArrayHasKey('none', $result['bottom_padding']['#options']);
  }

  /**
   * Test addPaddingMarginElements bottom_margin configuration.
   *
   * // Covers: addPaddingMarginElements
   */
  public function testAddPaddingMarginElementsBottomMargin(): void {
    $form = [];
    $form_state = new FormState();

    $result = $this->traitObject->addPaddingMarginElementsPublic($form, $form_state);

    $this->assertEquals('select', $result['bottom_margin']['#type']);
    $this->assertEquals('Space below section', $result['bottom_margin']['#title']->render());
    $this->assertArrayHasKey('#options', $result['bottom_margin']);
    $this->assertArrayHasKey('none', $result['bottom_margin']['#options']);
  }

  /**
   * Test addPaddingMarginElements with existing configuration.
   *
   * // Covers: addPaddingMarginElements
   */
  public function testAddPaddingMarginElementsWithConfig(): void {
    $this->traitObject->configuration['top_padding'] = 'more';
    $this->traitObject->configuration['bottom_padding'] = 'none';
    $this->traitObject->configuration['bottom_margin'] = 'none';

    $form = [];
    $form_state = new FormState();

    $result = $this->traitObject->addPaddingMarginElementsPublic($form, $form_state);

    $this->assertEquals('more', $result['top_padding']['#default_value']);
    $this->assertEquals('none', $result['bottom_padding']['#default_value']);
    $this->assertEquals('none', $result['bottom_margin']['#default_value']);
  }

  /**
   * Test submitBackgroundForm processes color value.
   *
   * // Covers: submitBackgroundForm
   */
  public function testSubmitBackgroundForm(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', '#FF0000');

    $this->traitObject->submitBackgroundFormPublic($form, $form_state);

    $this->assertEquals('ff0000', $this->traitObject->configuration['bg_color']);
  }

  /**
   * Test submitBackgroundForm handles lowercase.
   *
   * // Covers: submitBackgroundForm
   */
  public function testSubmitBackgroundFormLowercase(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', '#ABCDEF');

    $this->traitObject->submitBackgroundFormPublic($form, $form_state);

    $this->assertEquals('abcdef', $this->traitObject->configuration['bg_color']);
  }

  /**
   * Test submitBackgroundForm removes hash.
   *
   * // Covers: submitBackgroundForm
   */
  public function testSubmitBackgroundFormRemovesHash(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', 'ABCDEF');

    $this->traitObject->submitBackgroundFormPublic($form, $form_state);

    $this->assertEquals('abcdef', $this->traitObject->configuration['bg_color']);
  }

  /**
   * Test submitPaddingMarginForm sets all values.
   *
   * // Covers: submitPaddingMarginForm
   */
  public function testSubmitPaddingMarginForm(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('top_padding', 'more');
    $form_state->setValue('bottom_padding', 'none');
    $form_state->setValue('bottom_margin', 'none');

    $this->traitObject->submitPaddingMarginFormPublic($form, $form_state);

    $this->assertEquals('more', $this->traitObject->configuration['top_padding']);
    $this->assertEquals('none', $this->traitObject->configuration['bottom_padding']);
    $this->assertEquals('none', $this->traitObject->configuration['bottom_margin']);
  }

  /**
   * Test submitPaddingMarginForm with NULL values.
   *
   * // Covers: submitPaddingMarginForm
   */
  public function testSubmitPaddingMarginFormNullValues(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('top_padding', NULL);
    $form_state->setValue('bottom_padding', NULL);
    $form_state->setValue('bottom_margin', NULL);

    $this->traitObject->submitPaddingMarginFormPublic($form, $form_state);

    $this->assertNull($this->traitObject->configuration['top_padding']);
    $this->assertNull($this->traitObject->configuration['bottom_padding']);
    $this->assertNull($this->traitObject->configuration['bottom_margin']);
  }

}
