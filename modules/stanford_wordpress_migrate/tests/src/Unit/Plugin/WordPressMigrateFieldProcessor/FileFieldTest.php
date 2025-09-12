<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\WordPressMigrateFieldProcessor;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\FileField;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for FileField plugin.
 */
class FileFieldTest extends UnitTestCase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\FileField
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = new FileField([], 'file', [
      'label' => 'File',
      'fieldType' => ['file'],
    ]);
  }

  /**
   * Test plugin construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(FileField::class, $this->plugin);
  }

  /**
   * Test getProcess method.
   */
  public function testGetProcess(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $process = $this->plugin->getProcess($field);

    $this->assertIsArray($process);
    $this->assertCount(2, $process);

    // Check skip_on_empty from parent
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);

    // Check migration_lookup plugin
    $this->assertEquals('migration_lookup', $process[1]['plugin']);
    $this->assertEquals('wordpress_files:files', $process[1]['migration']);
    $this->assertEquals('wordpress_files:files', $process[1]['stub_id']);
  }

  /**
   * Test getProcess method with column parameter.
   */
  public function testGetProcessWithColumn(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $process = $this->plugin->getProcess($field, 'target_id');

    $this->assertIsArray($process);
    $this->assertCount(2, $process);
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);
    $this->assertEquals('migration_lookup', $process[1]['plugin']);
  }

  /**
   * Test plugin attribute configuration.
   */
  public function testPluginAttribute(): void {
    $reflection = new \ReflectionClass(FileField::class);
    $attributes = $reflection->getAttributes();

    $this->assertNotEmpty($attributes);
    $attributeInstance = $attributes[0]->newInstance();
    $this->assertEquals('file', $attributeInstance->id);
    $this->assertEquals(['file'], $attributeInstance->fieldType);
  }

  /**
   * Test label method inherited from base.
   */
  public function testLabel(): void {
    $label = $this->plugin->label();
    $this->assertEquals('File', $label);
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
