<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_layout_paragraphs\Kernel\Layouts;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_layout_paragraphs\Layouts\OneColumn;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Kernel tests for OneColumn layout.
 */
#[CoversClass(OneColumn::class)]
class OneColumnTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system'];

  /**
   * The layout instance.
   *
   * @var \Drupal\stanford_layout_paragraphs\Layouts\OneColumn
   */
  protected $layout;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $configuration = [];
    $plugin_id = 'one_column';
    $plugin_definition = [
      'label' => 'One Column',
      'category' => 'Test',
      'template' => 'templates/one-column',
      'regions' => [
        'main' => ['label' => 'Main'],
      ],
    ];

    $this->layout = new OneColumn($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Test buildConfigurationForm adds background color element.
   *
   * // Covers: buildConfigurationForm
   */
  public function testBuildConfigurationForm(): void {
    $form = [];
    $form_state = new FormState();

    $result = $this->layout->buildConfigurationForm($form, $form_state);

    $this->assertArrayHasKey('bg_color', $result);
    $this->assertEquals('textfield', $result['bg_color']['#type']);
    $this->assertEquals('Background Color', $result['bg_color']['#title']->render());
    $this->assertEquals(7, $result['bg_color']['#maxlength']);
    $this->assertArrayHasKey('#attached', $result['bg_color']);
    $this->assertArrayHasKey('library', $result['bg_color']['#attached']);
    $this->assertContains('color_field/color-field-widget-box', $result['bg_color']['#attached']['library']);
  }

  /**
   * Test buildConfigurationForm adds padding elements.
   *
   * // Covers: buildConfigurationForm
   */
  public function testBuildConfigurationFormPadding(): void {
    $form = [];
    $form_state = new FormState();

    $result = $this->layout->buildConfigurationForm($form, $form_state);

    $this->assertArrayHasKey('top_padding', $result);
    $this->assertArrayHasKey('bottom_padding', $result);
    $this->assertArrayHasKey('bottom_margin', $result);

    $this->assertEquals('select', $result['top_padding']['#type']);
    $this->assertEquals('select', $result['bottom_padding']['#type']);
    $this->assertEquals('select', $result['bottom_margin']['#type']);
  }

  /**
   * Test buildConfigurationForm with existing configuration.
   *
   * // Covers: buildConfigurationForm
   */
  public function testBuildConfigurationFormWithExistingConfig(): void {
    $configuration = [
      'bg_color' => 'ff0000',
      'top_padding' => 'more',
      'bottom_padding' => 'none',
      'bottom_margin' => 'none',
    ];
    $plugin_id = 'one_column';
    $plugin_definition = [
      'label' => 'One Column',
      'category' => 'Test',
      'template' => 'templates/one-column',
      'regions' => ['main' => ['label' => 'Main']],
    ];

    $layout = new OneColumn($configuration, $plugin_id, $plugin_definition);

    $form = [];
    $form_state = new FormState();

    $result = $layout->buildConfigurationForm($form, $form_state);

    $this->assertEquals('#ff0000', $result['bg_color']['#default_value']);
    $this->assertEquals('more', $result['top_padding']['#default_value']);
    $this->assertEquals('none', $result['bottom_padding']['#default_value']);
    $this->assertEquals('none', $result['bottom_margin']['#default_value']);
  }

  /**
   * Test submitConfigurationForm processes background color.
   *
   * // Covers: submitConfigurationForm
   */
  public function testSubmitConfigurationForm(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', '#FF0000');
    $form_state->setValue('top_padding', 'more');
    $form_state->setValue('bottom_padding', 'none');
    $form_state->setValue('bottom_margin', 'none');

    $this->layout->submitConfigurationForm($form, $form_state);

    $config = $this->layout->getConfiguration();
    $this->assertEquals('ff0000', $config['bg_color']);
    $this->assertEquals('more', $config['top_padding']);
    $this->assertEquals('none', $config['bottom_padding']);
    $this->assertEquals('none', $config['bottom_margin']);
  }

  /**
   * Test submitConfigurationForm handles color without hash.
   *
   * // Covers: submitConfigurationForm
   */
  public function testSubmitConfigurationFormColorWithoutHash(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', 'ABCDEF');
    $form_state->setValue('top_padding', NULL);
    $form_state->setValue('bottom_padding', NULL);
    $form_state->setValue('bottom_margin', NULL);

    $this->layout->submitConfigurationForm($form, $form_state);

    $config = $this->layout->getConfiguration();
    $this->assertEquals('abcdef', $config['bg_color']);
  }

  /**
   * Test submitConfigurationForm with empty values.
   *
   * // Covers: submitConfigurationForm
   */
  public function testSubmitConfigurationFormEmpty(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', '');
    $form_state->setValue('top_padding', '');
    $form_state->setValue('bottom_padding', '');
    $form_state->setValue('bottom_margin', '');

    $this->layout->submitConfigurationForm($form, $form_state);

    $config = $this->layout->getConfiguration();
    $this->assertEquals('', $config['bg_color']);
  }

}
