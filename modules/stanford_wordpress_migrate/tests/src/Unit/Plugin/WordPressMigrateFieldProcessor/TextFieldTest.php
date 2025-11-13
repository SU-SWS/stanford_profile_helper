<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\WordPressMigrateFieldProcessor;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\TextField;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for TextField plugin.
 */
class TextFieldTest extends UnitTestCase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\TextField
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
    $this->migration->expects($this->any())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');

    $this->plugin = new TextField([
      'migration' => $this->migration,
    ], 'text', [
      'label' => 'Long Text',
      'fieldType' => ['text', 'text_long', 'text_with_summary'],
    ]);
  }

  /**
   * Test plugin construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(TextField::class, $this->plugin);
  }

  /**
   * Test getProcess method.
   */
  public function testGetProcess(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $process = $this->plugin->getProcess($field);

    $this->assertIsArray($process);
    $this->assertCount(3, $process);

    // Check skip_on_empty from parent
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);

    // Check html_entity_decode callback
    $this->assertEquals('callback', $process[1]['plugin']);
    $this->assertEquals('html_entity_decode', $process[1]['callable']);

    // Check media_wysiwyg_parse plugin
    $this->assertEquals('media_wysiwyg_parse', $process[2]['plugin']);
    $this->assertEquals('https://example.com', $process[2]['image_domain']);
  }

  /**
   * Test getProcess method with column parameter.
   */
  public function testGetProcessWithColumn(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $process = $this->plugin->getProcess($field, 'value');

    $this->assertIsArray($process);
    $this->assertCount(3, $process);
    $this->assertEquals('media_wysiwyg_parse', $process[2]['plugin']);
  }

  /**
   * Test getExtraProcess method.
   */
  public function testGetExtraProcess(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $field->expects($this->once())
      ->method('getName')
      ->willReturn('field_body');

    $extraProcess = $this->plugin->getExtraProcess($field);

    $this->assertIsArray($extraProcess);
    $this->assertArrayHasKey('field_body/format', $extraProcess);
    $this->assertEquals('default_value', $extraProcess['field_body/format']['plugin']);
    $this->assertEquals('stanford_html', $extraProcess['field_body/format']['default_value']);
  }

  /**
   * Test getMultiplePlugin method with single cardinality.
   */
  public function testGetMultiplePluginSingleCardinality(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $fieldStorage->expects($this->once())
      ->method('getCardinality')
      ->willReturn(1);

    $multiplePlugin = $this->plugin->getMultiplePlugin($field);

    $this->assertEquals('concat', $multiplePlugin);
  }

  /**
   * Test getMultiplePlugin method with unlimited cardinality.
   */
  public function testGetMultiplePluginUnlimitedCardinality(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $fieldStorage->expects($this->once())
      ->method('getCardinality')
      ->willReturn(-1);

    $multiplePlugin = $this->plugin->getMultiplePlugin($field);

    $this->assertEquals('get', $multiplePlugin);
  }

  /**
   * Test getMultiplePlugin method with multiple cardinality.
   */
  public function testGetMultiplePluginMultipleCardinality(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);
    $fieldStorage = $this->createMock(FieldStorageDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getFieldStorageDefinition')
      ->willReturn($fieldStorage);

    $fieldStorage->expects($this->once())
      ->method('getCardinality')
      ->willReturn(5);

    $multiplePlugin = $this->plugin->getMultiplePlugin($field);

    $this->assertEquals('get', $multiplePlugin);
  }

  /**
   * Test plugin attribute configuration.
   */
  public function testPluginAttribute(): void {
    $reflection = new \ReflectionClass(TextField::class);
    $attributes = $reflection->getAttributes();

    $this->assertNotEmpty($attributes);
    $attributeInstance = $attributes[0]->newInstance();
    $this->assertEquals('text', $attributeInstance->id);
    $this->assertEquals(['text', 'text_long', 'text_with_summary'], $attributeInstance->fieldType);
  }

  /**
   * Test label method inherited from base.
   */
  public function testLabel(): void {
    $label = $this->plugin->label();
    $this->assertEquals('Long Text', $label);
  }

}
