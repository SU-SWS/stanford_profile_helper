<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginBase;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for WordPressMigrateFieldProcessorPluginBase.
 */
class WordPressMigrateFieldProcessorPluginBaseTest extends UnitTestCase {

  /**
   * Mock plugin instance.
   *
   * @var \Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginBase
   */
  protected $plugin;

  /**
   * Mock migration.
   *
   * @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->migration = $this->createMock(WordPressMigrationInterface::class);

    // Create anonymous class to test abstract base class
    $this->plugin = new class([], 'test_plugin', ['label' => 'Test Plugin']) extends WordPressMigrateFieldProcessorPluginBase {

    };
  }

  /**
   * Test plugin construction.
   */
  public function testConstruct(): void {
    $configuration = ['migration' => $this->migration];
    $plugin = new class($configuration, 'test_plugin', ['label' => 'Test Plugin']) extends WordPressMigrateFieldProcessorPluginBase {

    };

    $this->assertInstanceOf(WordPressMigrateFieldProcessorPluginBase::class, $plugin);
  }

  /**
   * Test label method.
   */
  public function testLabel(): void {
    $result = $this->plugin->label();
    $this->assertEquals('Test Plugin', $result);
  }

  /**
   * Test getFieldColumns method with single column field.
   */
  public function testGetFieldColumnsSingle(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $fieldStorage->expects($this->once())
      ->method('getColumns')
      ->willReturn(['value' => []]);

    $field->expects($this->once())
      ->method('getName')
      ->willReturn('test_field');

    $field->expects($this->once())
      ->method('getLabel')
      ->willReturn('Test Field');

    $result = $this->plugin->getFieldColumns($field);
    $expected = ['test_field' => 'Test Field'];
    $this->assertEquals($expected, $result);
  }

  /**
   * Test getFieldColumns method with multi-column field.
   */
  public function testGetFieldColumnsMultiple(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $fieldStorage->expects($this->once())
      ->method('getColumns')
      ->willReturn(['value' => [], 'format' => []]);

    $field->expects($this->exactly(2))
      ->method('getName')
      ->willReturn('test_field');

    $field->expects($this->exactly(2))
      ->method('getLabel')
      ->willReturn('Test Field');

    $result = $this->plugin->getFieldColumns($field);
    $expected = [
      'test_field/value' => 'Test Field: value',
      'test_field/format' => 'Test Field: format',
    ];
    $this->assertEquals($expected, $result);
  }

  /**
   * Test getConstants method.
   */
  public function testGetConstants(): void {
    $result = $this->plugin->getConstants();
    $this->assertEquals([], $result);
  }

  /**
   * Test getProcess method.
   */
  public function testGetProcess(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $result = $this->plugin->getProcess($field);
    $expected = [
      ['plugin' => 'skip_on_empty', 'method' => 'process'],
    ];
    $this->assertEquals($expected, $result);
  }

  /**
   * Test getExtraProcess method.
   */
  public function testGetExtraProcess(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $result = $this->plugin->getExtraProcess($field);
    $this->assertEquals([], $result);
  }

  /**
   * Test getMultiplePlugin method with single cardinality.
   */
  public function testGetMultiplePluginSingle(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $fieldStorage->expects($this->once())
      ->method('getCardinality')
      ->willReturn(1);

    $result = $this->plugin->getMultiplePlugin($field);
    $this->assertEquals('null_coalesce', $result);
  }

  /**
   * Test getMultiplePlugin method with multiple cardinality.
   */
  public function testGetMultiplePluginMultiple(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $fieldStorage->expects($this->once())
      ->method('getCardinality')
      ->willReturn(-1); // Unlimited

    $result = $this->plugin->getMultiplePlugin($field);
    $this->assertEquals('get', $result);
  }

  /**
   * Test setWordPressMigration method.
   */
  public function testSetWordPressMigration(): void {
    $result = $this->plugin->setWordPressMigration($this->migration);
    $this->assertSame($this->plugin, $result);
  }

}
