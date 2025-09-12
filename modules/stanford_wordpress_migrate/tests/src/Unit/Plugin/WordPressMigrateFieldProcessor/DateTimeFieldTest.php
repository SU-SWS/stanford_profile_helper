<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\WordPressMigrateFieldProcessor;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItem;
use Drupal\datetime\Plugin\Field\FieldType\DateTimeItemInterface;
use Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\DateTimeField;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for DateTimeField plugin.
 */
class DateTimeFieldTest extends UnitTestCase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\DateTimeField
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = new DateTimeField([], 'datetime', [
      'label' => 'Date Time',
      'fieldType' => ['datetime', 'daterange'],
    ]);
  }

  /**
   * Test plugin construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(DateTimeField::class, $this->plugin);
  }

  /**
   * Test getProcess method with datetime field.
   */
  public function testGetProcessDateTimeField(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $fieldStorage->expects($this->once())
      ->method('getSetting')
      ->with('datetime_type')
      ->willReturn(DateTimeItem::DATETIME_TYPE_DATETIME);

    $process = $this->plugin->getProcess($field);

    $this->assertIsArray($process);
    $this->assertCount(4, $process);

    // Check skip_on_empty from parent
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);

    // Check strtotime callback
    $this->assertEquals('callback', $process[1]['plugin']);
    $this->assertEquals('strtotime', $process[1]['callable']);

    // Check skip_on_empty after strtotime
    $this->assertEquals('skip_on_empty', $process[2]['plugin']);
    $this->assertEquals('process', $process[2]['method']);

    // Check format_date plugin with datetime format
    $this->assertEquals('format_date', $process[3]['plugin']);
    $this->assertEquals('U', $process[3]['from_format']);
    $this->assertEquals(DateTimeItemInterface::DATETIME_STORAGE_FORMAT, $process[3]['to_format']);
  }

  /**
   * Test getProcess method with date-only field.
   */
  public function testGetProcessDateField(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $fieldStorage->expects($this->once())
      ->method('getSetting')
      ->with('datetime_type')
      ->willReturn(DateTimeItem::DATETIME_TYPE_DATE);

    $process = $this->plugin->getProcess($field);

    $this->assertIsArray($process);
    $this->assertCount(4, $process);

    // Check format_date plugin uses date format
    $this->assertEquals('format_date', $process[3]['plugin']);
    $this->assertEquals('U', $process[3]['from_format']);
    $this->assertEquals(DateTimeItemInterface::DATE_STORAGE_FORMAT, $process[3]['to_format']);
  }

  /**
   * Test getProcess method with column parameter.
   */
  public function testGetProcessWithColumn(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $fieldStorage->expects($this->once())
      ->method('getSetting')
      ->with('datetime_type')
      ->willReturn(DateTimeItem::DATETIME_TYPE_DATETIME);

    $process = $this->plugin->getProcess($field, 'value');

    $this->assertIsArray($process);
    $this->assertCount(4, $process);
    $this->assertEquals('format_date', $process[3]['plugin']);
  }

  /**
   * Test plugin attribute configuration.
   */
  public function testPluginAttribute(): void {
    $reflection = new \ReflectionClass(DateTimeField::class);
    $attributes = $reflection->getAttributes();

    $this->assertNotEmpty($attributes);
    $attributeInstance = $attributes[0]->newInstance();
    $this->assertEquals('datetime', $attributeInstance->id);
    $this->assertEquals(['datetime', 'daterange'], $attributeInstance->fieldType);
  }

  /**
   * Test label method inherited from base.
   */
  public function testLabel(): void {
    $label = $this->plugin->label();
    $this->assertEquals('Date Time', $label);
  }

}
