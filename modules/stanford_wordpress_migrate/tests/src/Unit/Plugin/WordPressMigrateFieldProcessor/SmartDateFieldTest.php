<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\WordPressMigrateFieldProcessor;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\SmartDateField;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for SmartDateField plugin.
 */
class SmartDateFieldTest extends UnitTestCase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\SmartDateField
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = new SmartDateField([], 'smartdate', [
      'label' => 'Smart Date',
      'fieldType' => ['smartdate'],
    ]);
  }

  /**
   * Test plugin construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(SmartDateField::class, $this->plugin);
  }

  /**
   * Test getProcess method without column.
   */
  public function testGetProcessNoColumn(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $process = $this->plugin->getProcess($field);

    $this->assertIsArray($process);
    $this->assertCount(1, $process);
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);
  }

  /**
   * Test getProcess method with value column.
   */
  public function testGetProcessValueColumn(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $process = $this->plugin->getProcess($field, 'value');

    $this->assertIsArray($process);
    $this->assertCount(3, $process);

    // Check skip_on_empty from parent
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);

    // Check strtotime callback
    $this->assertEquals('callback', $process[1]['plugin']);
    $this->assertEquals('strtotime', $process[1]['callable']);

    // Check skip_on_empty after strtotime
    $this->assertEquals('skip_on_empty', $process[2]['plugin']);
    $this->assertEquals('process', $process[2]['method']);
  }

  /**
   * Test getProcess method with end_value column.
   */
  public function testGetProcessEndValueColumn(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $process = $this->plugin->getProcess($field, 'end_value');

    $this->assertIsArray($process);
    $this->assertCount(3, $process);

    // Check skip_on_empty from parent
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);

    // Check strtotime callback
    $this->assertEquals('callback', $process[1]['plugin']);
    $this->assertEquals('strtotime', $process[1]['callable']);

    // Check skip_on_empty after strtotime
    $this->assertEquals('skip_on_empty', $process[2]['plugin']);
    $this->assertEquals('process', $process[2]['method']);
  }

  /**
   * Test getProcess method with other column.
   */
  public function testGetProcessOtherColumn(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $process = $this->plugin->getProcess($field, 'duration');

    $this->assertIsArray($process);
    $this->assertCount(1, $process);
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);
  }

  /**
   * Test plugin attribute configuration.
   */
  public function testPluginAttribute(): void {
    $reflection = new \ReflectionClass(SmartDateField::class);
    $attributes = $reflection->getAttributes();

    $this->assertNotEmpty($attributes);
    $attributeInstance = $attributes[0]->newInstance();
    $this->assertEquals('smartdate', $attributeInstance->id);
    $this->assertEquals(['smartdate'], $attributeInstance->fieldType);
  }

  /**
   * Test label method inherited from base.
   */
  public function testLabel(): void {
    $label = $this->plugin->label();
    $this->assertEquals('Smart Date', $label);
  }

  /**
   * Test all inherited methods work.
   */
  public function testInheritedMethods(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    // Test getConstants
    $constants = $this->plugin->getConstants();
    $this->assertEquals([], $constants);

    // Test getExtraProcess
    $extraProcess = $this->plugin->getExtraProcess($field);
    $this->assertEquals([], $extraProcess);
  }

}
