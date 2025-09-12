<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\WordPressMigrateFieldProcessor;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\StringField;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for StringField plugin.
 */
class StringFieldTest extends UnitTestCase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\StringField
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = new StringField([], 'string', [
      'label' => 'String',
      'fieldType' => [
        'string',
        'string_long',
        'email',
        'list_string',
        'telephone',
        'name',
      ],
    ]);
  }

  /**
   * Test plugin construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(StringField::class, $this->plugin);
  }

  /**
   * Test getProcess method with string field.
   */
  public function testGetProcessStringField(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $field->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $fieldStorage->expects($this->once())
      ->method('getSetting')
      ->with('max_length')
      ->willReturn(255);

    $process = $this->plugin->getProcess($field);

    $this->assertIsArray($process);
    $this->assertCount(4, $process); // skip_on_empty + html_entity_decode + strip_tags + substr

    // Check the first process plugin from parent
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);

    // Check html_entity_decode callback
    $this->assertEquals('callback', $process[1]['plugin']);
    $this->assertEquals('html_entity_decode', $process[1]['callable']);

    // Check strip_tags callback
    $this->assertEquals('callback', $process[2]['plugin']);
    $this->assertEquals('strip_tags', $process[2]['callable']);

    // Check substr plugin for max length
    $this->assertEquals('substr', $process[3]['plugin']);
    $this->assertEquals(0, $process[3]['start']);
    $this->assertEquals(255, $process[3]['length']);
  }

  /**
   * Test getProcess method with string_long field.
   */
  public function testGetProcessStringLongField(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $field->expects($this->once())
      ->method('getType')
      ->willReturn('string_long');

    $process = $this->plugin->getProcess($field);

    $this->assertIsArray($process);
    $this->assertCount(3, $process); // skip_on_empty + html_entity_decode + strip_tags (no substr for string_long)

    // Check there's no substr plugin for string_long fields
    foreach ($process as $processPlugin) {
      $this->assertNotEquals('substr', $processPlugin['plugin'] ?? '');
    }
  }

  /**
   * Test getProcess method with custom max length.
   */
  public function testGetProcessCustomMaxLength(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $field->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $fieldStorage->expects($this->once())
      ->method('getSetting')
      ->with('max_length')
      ->willReturn(100);

    $process = $this->plugin->getProcess($field);

    $this->assertIsArray($process);
    $this->assertCount(4, $process);

    // Check substr plugin uses custom max length
    $this->assertEquals('substr', $process[3]['plugin']);
    $this->assertEquals(100, $process[3]['length']);
  }

  /**
   * Test getProcess method with no max length setting.
   */
  public function testGetProcessNoMaxLength(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $field->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $fieldStorage->expects($this->once())
      ->method('getSetting')
      ->with('max_length')
      ->willReturn(NULL);

    $process = $this->plugin->getProcess($field);

    $this->assertIsArray($process);
    $this->assertCount(4, $process);

    // Check substr plugin uses default 255
    $this->assertEquals('substr', $process[3]['plugin']);
    $this->assertEquals(255, $process[3]['length']);
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

    $field->expects($this->once())
      ->method('getType')
      ->willReturn('string');

    $fieldStorage->expects($this->once())
      ->method('getSetting')
      ->with('max_length')
      ->willReturn(255);

    $process = $this->plugin->getProcess($field, 'value');

    $this->assertIsArray($process);
    $this->assertCount(4, $process);

    // Same processing regardless of column
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);
    $this->assertEquals('callback', $process[1]['plugin']);
    $this->assertEquals('callback', $process[2]['plugin']);
    $this->assertEquals('substr', $process[3]['plugin']);
  }

}
