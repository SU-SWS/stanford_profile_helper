<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\Plugin\Field\FieldFormatter;

use Drupal\Core\Entity\Entity\EntityViewDisplay;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Form\FormState;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class TimeDurationFormatterTest.
 */
#[Group('stanford_profile_helper')]
class TimeDurationFormatterTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'stanford_profile_helper',
    'node',
    'field',
    'user',
    'config_pages',
  ];

  /**
   * {@inheritdoc}
   */
  public function setup(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig('system');
    $this->installSchema('node', ['node_access']);

    NodeType::create(['type' => 'page'])->save();
    FieldStorageConfig::create([
      'type' => 'integer',
      'field_name' => 'field_duration',
      'entity_type' => 'node',
      'cardinality' => FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED,
    ])->save();
    FieldConfig::create([
      'field_name' => 'field_duration',
      'entity_type' => 'node',
      'bundle' => 'page',
    ])->save();
  }

  /**
   * Test default settings.
   */
  public function testDefaultSettings() {
    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_duration', ['type' => 'time_duration'])
      ->save();

    $display = EntityViewDisplay::load('node.page.default');
    $component = $display->getComponent('field_duration');

    $this->assertEquals('short', $component['settings']['style']);
    $this->assertEquals([], $component['settings']['units']);
  }

  /**
   * Test settings form.
   */
  public function testSettingsForm() {
    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_duration', [
      'type' => 'time_duration',
      'settings' => ['style' => 'long', 'units' => ['hour', 'min']],
    ])->save();

    $display = EntityViewDisplay::load('node.page.default');
    $formatter = $display->getRenderer('field_duration');

    $form = [];
    $form_state = new FormState();
    $settings_form = $formatter->settingsForm($form, $form_state);

    // Verify style field exists and has correct properties.
    $this->assertArrayHasKey('style', $settings_form);
    $this->assertEquals('select', $settings_form['style']['#type']);
    $this->assertEquals('long', $settings_form['style']['#default_value']);
    $this->assertArrayHasKey('short', $settings_form['style']['#options']);
    $this->assertArrayHasKey('long', $settings_form['style']['#options']);

    // Verify units field exists and has correct properties.
    $this->assertArrayHasKey('units', $settings_form);
    $this->assertEquals('checkboxes', $settings_form['units']['#type']);
    $this->assertEquals([
      'hour',
      'min',
    ], $settings_form['units']['#default_value']);
    $this->assertArrayHasKey('hour', $settings_form['units']['#options']);
    $this->assertArrayHasKey('min', $settings_form['units']['#options']);
    $this->assertArrayHasKey('sec', $settings_form['units']['#options']);
  }

  /**
   * Test short format with various durations.
   */
  public function testShortFormat() {
    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_duration', [
      'type' => 'time_duration',
      'settings' => ['style' => 'short', 'units' => []],
    ])->save();

    // Test 0 seconds.
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'field_duration' => [0],
    ]);
    $view = $node->field_duration->view([
      'type' => 'time_duration',
      'settings' => ['style' => 'short', 'units' => []],
    ]);
    $output = (string) $this->container->get('renderer')->renderRoot($view);
    $this->assertStringContainsString(':00', $output);

    // Test 45 seconds.
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'field_duration' => [45],
    ]);
    $view = $node->field_duration->view([
      'type' => 'time_duration',
      'settings' => ['style' => 'short', 'units' => []],
    ]);
    $output = (string) $this->container->get('renderer')->renderRoot($view);
    $this->assertStringContainsString('45', $output);

    // Test 90 seconds (1:30).
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'field_duration' => [90],
    ]);
    $view = $node->field_duration->view([
      'type' => 'time_duration',
      'settings' => ['style' => 'short', 'units' => []],
    ]);
    $output = (string) $this->container->get('renderer')->renderRoot($view);
    $this->assertStringContainsString('1:', $output);
    $this->assertStringContainsString('30', $output);

    // Test 3665 seconds (1:01:05).
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'field_duration' => [3665],
    ]);
    $view = $node->field_duration->view([
      'type' => 'time_duration',
      'settings' => ['style' => 'short', 'units' => []],
    ]);
    $output = (string) $this->container->get('renderer')->renderRoot($view);
    $this->assertStringContainsString('1:', $output);
    $this->assertStringContainsString('01:', $output);
    $this->assertStringContainsString('05', $output);
  }

  /**
   * Test short format with specific units enabled.
   */
  public function testShortFormatWithUnits() {
    // Test with only seconds.
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'field_duration' => [3665],
    ]);
    $view = $node->field_duration->view([
      'type' => 'time_duration',
      'settings' => ['style' => 'short', 'units' => ['sec']],
    ]);
    $output = (string) $this->container->get('renderer')->renderRoot($view);
    $this->assertStringContainsString('5', $output);
    $this->assertStringNotContainsString('01:', $output);

    // Test with hours and minutes only (simulating checkbox behavior with 0 for unchecked).
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'field_duration' => [3665],
    ]);
    $view = $node->field_duration->view([
      'type' => 'time_duration',
      'settings' => [
        'style' => 'short',
        'units' => ['hour', 'min'],
      ],
    ]);
    $output = (string) $this->container->get('renderer')->renderRoot($view);
    $this->assertStringContainsString('1:', $output);
    $this->assertStringContainsString('01', $output);
    // Seconds should not be displayed when filtered out.
    $this->assertStringNotContainsString('05', $output);
  }

  /**
   * Test long format with various durations.
   */
  public function testLongFormat() {
    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_duration', [
      'type' => 'time_duration',
      'settings' => ['style' => 'long', 'units' => []],
    ])->save();

    // Test 45 seconds.
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'field_duration' => [45],
    ]);
    $view = $node->field_duration->view([
      'type' => 'time_duration',
      'settings' => ['style' => 'long', 'units' => []],
    ]);
    $output = (string) $this->container->get('renderer')->renderRoot($view);
    $this->assertStringContainsString('seconds', $output);
    $this->assertStringContainsString('45', $output);

    // Test 90 seconds.
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'field_duration' => [90],
    ]);
    $view = $node->field_duration->view([
      'type' => 'time_duration',
      'settings' => ['style' => 'long', 'units' => []],
    ]);
    $output = (string) $this->container->get('renderer')->renderRoot($view);
    $this->assertStringContainsString('minutes', $output);
    $this->assertStringContainsString('seconds', $output);

    // Test 3665 seconds (1 hour, 1 minute, 5 seconds).
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'field_duration' => [3665],
    ]);
    $view = $node->field_duration->view([
      'type' => 'time_duration',
      'settings' => ['style' => 'long', 'units' => []],
    ]);
    $output = (string) $this->container->get('renderer')->renderRoot($view);
    $this->assertStringContainsString('hours', $output);
    $this->assertStringContainsString('minutes', $output);
    $this->assertStringContainsString('seconds', $output);
  }

  /**
   * Test long format with specific units enabled.
   */
  public function testLongFormatWithUnits() {
    // Test with only hours (simulating checkbox with 0 for unchecked).
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'field_duration' => [3665],
    ]);
    $view = $node->field_duration->view([
      'type' => 'time_duration',
      'settings' => [
        'style' => 'long',
        'units' => ['hour'],
      ],
    ]);
    $output = (string) $this->container->get('renderer')->renderRoot($view);
    $this->assertStringContainsString('hours', $output);
    $this->assertStringNotContainsString('minutes', $output);
    $this->assertStringNotContainsString('seconds', $output);

    // Test with minutes and seconds (simulating checkbox with 0 for unchecked).
    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'field_duration' => [3665],
    ]);
    $view = $node->field_duration->view([
      'type' => 'time_duration',
      'settings' => [
        'style' => 'long',
        'units' => ['min', 'sec'],
      ],
    ]);
    $output = (string) $this->container->get('renderer')->renderRoot($view);
    $this->assertStringNotContainsString('hours', $output);
    $this->assertStringContainsString('minutes', $output);
    $this->assertStringContainsString('seconds', $output);
  }

  /**
   * Test multiple field values.
   */
  public function testMultipleValues() {
    EntityViewDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ])->setComponent('field_duration', [
      'type' => 'time_duration',
      'settings' => ['style' => 'short', 'units' => []],
    ])->save();

    $node = Node::create([
      'type' => 'page',
      'title' => 'Test',
      'field_duration' => [45, 90, 3665],
    ]);
    $view = $node->field_duration->view([
      'type' => 'time_duration',
      'settings' => ['style' => 'short', 'units' => []],
    ]);
    $output = (string) $this->container->get('renderer')->renderRoot($view);

    // Should contain all three values.
    $this->assertStringContainsString('45', $output);
    $this->assertStringContainsString('30', $output);
    $this->assertStringContainsString('05', $output);
  }

}
