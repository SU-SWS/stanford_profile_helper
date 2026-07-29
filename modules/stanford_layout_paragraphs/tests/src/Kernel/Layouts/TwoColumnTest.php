<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_layout_paragraphs\Kernel\Layouts;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_layout_paragraphs\Layouts\TwoColumn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for TwoColumn layout.
 */
#[CoversClass(TwoColumn::class)]
#[RunTestsInSeparateProcesses]
class TwoColumnTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'layout_builder'];

  /**
   * The layout instance.
   *
   * @var \Drupal\stanford_layout_paragraphs\Layouts\TwoColumn
   */
  protected $layout;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $configuration = [];
    $plugin_id = 'two_column';
    $plugin_definition = [
      'label' => 'Two Column',
      'category' => 'Test',
      'template' => 'templates/two-column',
      'regions' => [
        'first' => ['label' => 'First'],
        'second' => ['label' => 'Second'],
      ],
    ];

    $this->layout = new TwoColumn($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * Test defaultConfiguration.
   *
   * // Covers: defaultConfiguration
   */
  public function testDefaultConfiguration(): void {
    $config = $this->layout->defaultConfiguration();

    $this->assertArrayHasKey('bg_color', $config);
    $this->assertArrayHasKey('top_padding', $config);
    $this->assertArrayHasKey('bottom_padding', $config);
    $this->assertArrayHasKey('bottom_margin', $config);
    $this->assertArrayHasKey('vertical_dividers', $config);

    $this->assertNull($config['bg_color']);
    $this->assertNull($config['top_padding']);
    $this->assertNull($config['bottom_padding']);
    $this->assertNull($config['bottom_margin']);
    $this->assertNull($config['vertical_dividers']);
  }

  /**
   * Test buildConfigurationForm adds all elements.
   *
   * // Covers: buildConfigurationForm
   */
  public function testBuildConfigurationForm(): void {
    $form = [];
    $form_state = new FormState();

    $result = $this->layout->buildConfigurationForm($form, $form_state);

    $this->assertArrayHasKey('bg_color', $result);
    $this->assertArrayHasKey('top_padding', $result);
    $this->assertArrayHasKey('bottom_padding', $result);
    $this->assertArrayHasKey('bottom_margin', $result);
    $this->assertArrayHasKey('vertical_dividers', $result);

    $this->assertEquals('checkbox', $result['vertical_dividers']['#type']);
    $this->assertEquals('Add vertical dividers', $result['vertical_dividers']['#title']->render());
  }

  /**
   * Test buildConfigurationForm with existing configuration.
   *
   * // Covers: buildConfigurationForm
   */
  public function testBuildConfigurationFormWithExistingConfig(): void {
    $configuration = [
      'bg_color' => '00ff00',
      'top_padding' => 'more',
      'bottom_padding' => 'none',
      'bottom_margin' => 'none',
      'vertical_dividers' => TRUE,
    ];
    $plugin_id = 'two_column';
    $plugin_definition = [
      'label' => 'Two Column',
      'category' => 'Test',
      'template' => 'templates/two-column',
      'regions' => [
        'first' => ['label' => 'First'],
        'second' => ['label' => 'Second'],
      ],
    ];

    $layout = new TwoColumn($configuration, $plugin_id, $plugin_definition);

    $form = [];
    $form_state = new FormState();

    $result = $layout->buildConfigurationForm($form, $form_state);

    $this->assertEquals('#00ff00', $result['bg_color']['#default_value']);
    $this->assertEquals('more', $result['top_padding']['#default_value']);
    $this->assertTrue($result['vertical_dividers']['#default_value']);
  }

  /**
   * Test submitConfigurationForm processes all values.
   *
   * // Covers: submitConfigurationForm
   */
  public function testSubmitConfigurationForm(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', '#0000FF');
    $form_state->setValue('top_padding', 'more');
    $form_state->setValue('bottom_padding', 'none');
    $form_state->setValue('bottom_margin', 'none');
    $form_state->setValue('vertical_dividers', TRUE);

    $this->layout->submitConfigurationForm($form, $form_state);

    $config = $this->layout->getConfiguration();
    $this->assertEquals('0000ff', $config['bg_color']);
    $this->assertEquals('more', $config['top_padding']);
    $this->assertEquals('none', $config['bottom_padding']);
    $this->assertEquals('none', $config['bottom_margin']);
    $this->assertTrue($config['vertical_dividers']);
  }

  /**
   * Test submitConfigurationForm with vertical_dividers as FALSE.
   *
   * // Covers: submitConfigurationForm
   */
  public function testSubmitConfigurationFormVerticalDividersFalse(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', '');
    $form_state->setValue('top_padding', NULL);
    $form_state->setValue('bottom_padding', NULL);
    $form_state->setValue('bottom_margin', NULL);
    $form_state->setValue('vertical_dividers', FALSE);

    $this->layout->submitConfigurationForm($form, $form_state);

    $config = $this->layout->getConfiguration();
    $this->assertFalse($config['vertical_dividers']);
  }

  /**
   * Test submitConfigurationForm casts vertical_dividers to boolean.
   *
   * // Covers: submitConfigurationForm
   */
  public function testSubmitConfigurationFormVerticalDividersCast(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', '');
    $form_state->setValue('top_padding', NULL);
    $form_state->setValue('bottom_padding', NULL);
    $form_state->setValue('bottom_margin', NULL);
    $form_state->setValue('vertical_dividers', 1);

    $this->layout->submitConfigurationForm($form, $form_state);

    $config = $this->layout->getConfiguration();
    $this->assertTrue($config['vertical_dividers']);
    $this->assertIsBool($config['vertical_dividers']);
  }

  /**
   * Test submitConfigurationForm with 0 casts to false.
   *
   * // Covers: submitConfigurationForm
   */
  public function testSubmitConfigurationFormVerticalDividersZero(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setValue('bg_color', '');
    $form_state->setValue('top_padding', NULL);
    $form_state->setValue('bottom_padding', NULL);
    $form_state->setValue('bottom_margin', NULL);
    $form_state->setValue('vertical_dividers', 0);

    $this->layout->submitConfigurationForm($form, $form_state);

    $config = $this->layout->getConfiguration();
    $this->assertFalse($config['vertical_dividers']);
    $this->assertIsBool($config['vertical_dividers']);
  }

}
