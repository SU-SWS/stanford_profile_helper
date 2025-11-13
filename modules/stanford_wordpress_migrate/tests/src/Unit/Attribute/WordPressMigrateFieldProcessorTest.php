<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Attribute;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\stanford_wordpress_migrate\Attribute\WordPressMigrateFieldProcessor;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for WordPressMigrateFieldProcessor attribute.
 */
class WordPressMigrateFieldProcessorTest extends UnitTestCase {

  /**
   * Test attribute construction with basic parameters.
   */
  public function testConstructBasic(): void {
    $label = new TranslatableMarkup('Test Label');
    $fieldTypes = ['string', 'text'];

    $attribute = new WordPressMigrateFieldProcessor(
      'test_plugin',
      $label,
      $fieldTypes
    );

    $this->assertEquals('test_plugin', $attribute->id);
    $this->assertSame($label, $attribute->label);
    $this->assertEquals($fieldTypes, $attribute->fieldType);
  }

  /**
   * Test attribute construction with null label.
   */
  public function testConstructNullLabel(): void {
    $attribute = new WordPressMigrateFieldProcessor(
      'test_plugin',
      NULL,
      ['string']
    );

    $this->assertEquals('test_plugin', $attribute->id);
    $this->assertNull($attribute->label);
    $this->assertEquals(['string'], $attribute->fieldType);
  }

  /**
   * Test attribute construction with empty field types.
   */
  public function testConstructEmptyFieldTypes(): void {
    $label = new TranslatableMarkup('Test Label');

    $attribute = new WordPressMigrateFieldProcessor(
      'test_plugin',
      $label
    );

    $this->assertEquals('test_plugin', $attribute->id);
    $this->assertSame($label, $attribute->label);
    $this->assertEquals([], $attribute->fieldType);
  }

  /**
   * Test attribute properties are readonly.
   */
  public function testReadonlyProperties(): void {
    $reflection = new \ReflectionClass(WordPressMigrateFieldProcessor::class);

    $idProperty = $reflection->getProperty('id');
    $this->assertTrue($idProperty->isReadOnly());

    $labelProperty = $reflection->getProperty('label');
    $this->assertTrue($labelProperty->isReadOnly());

    $fieldTypeProperty = $reflection->getProperty('fieldType');
    $this->assertTrue($fieldTypeProperty->isReadOnly());
  }

  /**
   * Test attribute is actually an attribute.
   */
  public function testIsAttribute(): void {
    $reflection = new \ReflectionClass(WordPressMigrateFieldProcessor::class);
    $attributes = $reflection->getAttributes(\Attribute::class);

    $this->assertNotEmpty($attributes);
    $this->assertEquals(\Attribute::TARGET_CLASS, $attributes[0]->newInstance()->flags);
  }

}
