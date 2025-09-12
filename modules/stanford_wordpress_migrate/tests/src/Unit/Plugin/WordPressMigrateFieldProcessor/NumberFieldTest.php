<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\WordPressMigrateFieldProcessor;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\NumberField;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for NumberField plugin.
 */
class NumberFieldTest extends UnitTestCase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\NumberField
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = new NumberField([], 'number', [
      'label' => 'Number',
      'fieldType' => [],
    ]);
  }

  /**
   * Test plugin construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(NumberField::class, $this->plugin);
  }

  /**
   * Test plugin inherits from base class.
   */
  public function testInheritsFromBase(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    // Test that it uses parent implementation
    $process = $this->plugin->getProcess($field);
    $this->assertIsArray($process);
    $this->assertCount(1, $process);
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);
  }

  /**
   * Test plugin attribute configuration.
   */
  public function testPluginAttribute(): void {
    $reflection = new \ReflectionClass(NumberField::class);
    $attributes = $reflection->getAttributes();

    $this->assertNotEmpty($attributes);
    $attributeInstance = $attributes[0]->newInstance();
    $this->assertEquals('number', $attributeInstance->id);
    $this->assertEquals(['decimal', 'integer'], $attributeInstance->fieldType);
  }

  /**
   * Test label method inherited from base.
   */
  public function testLabel(): void {
    $label = $this->plugin->label();
    $this->assertEquals('Number', $label);
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

  /**
   * Test getProcess method with column parameter.
   */
  public function testGetProcessWithColumn(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $process = $this->plugin->getProcess($field, 'value');

    $this->assertIsArray($process);
    $this->assertCount(1, $process);
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);
  }

}
