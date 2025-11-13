<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\WordPressMigrateFieldProcessor;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\LinkField;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for LinkField plugin.
 */
class LinkFieldTest extends UnitTestCase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\LinkField
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->plugin = new LinkField([], 'link', [
      'label' => 'Link',
      'fieldType' => ['link'],
    ]);
  }

  /**
   * Test plugin construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(LinkField::class, $this->plugin);
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
   * Test getProcess method with uri column.
   */
  public function testGetProcessUriColumn(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $process = $this->plugin->getProcess($field, 'uri');

    $this->assertIsArray($process);
    $this->assertCount(3, $process);

    // Check skip_on_empty from parent
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);

    // Check str_replace plugin for www prefix
    $this->assertEquals('str_replace', $process[1]['plugin']);
    $this->assertTrue($process[1]['regex']);
    $this->assertEquals('/^www/', $process[1]['search']);
    $this->assertEquals('http://www', $process[1]['replace']);

    // Check url_check plugin
    $this->assertEquals('url_check', $process[2]['plugin']);
    $this->assertEquals('process', $process[2]['method']);
  }

  /**
   * Test getProcess method with title column.
   */
  public function testGetProcessTitleColumn(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $process = $this->plugin->getProcess($field, 'title');

    $this->assertIsArray($process);
    $this->assertCount(3, $process);

    // Check skip_on_empty from parent
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);

    // Check html_entity_decode callback
    $this->assertEquals('callback', $process[1]['plugin']);
    $this->assertEquals('html_entity_decode', $process[1]['callable']);

    // Check substr plugin for max length
    $this->assertEquals('substr', $process[2]['plugin']);
    $this->assertEquals(0, $process[2]['start']);
    $this->assertEquals(255, $process[2]['length']);
  }

  /**
   * Test getProcess method with other column.
   */
  public function testGetProcessOtherColumn(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $process = $this->plugin->getProcess($field, 'options');

    $this->assertIsArray($process);
    $this->assertCount(1, $process);
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);
  }

  /**
   * Test plugin attribute configuration.
   */
  public function testPluginAttribute(): void {
    $reflection = new \ReflectionClass(LinkField::class);
    $attributes = $reflection->getAttributes();

    $this->assertNotEmpty($attributes);
    $attributeInstance = $attributes[0]->newInstance();
    $this->assertEquals('link', $attributeInstance->id);
    $this->assertEquals(['link'], $attributeInstance->fieldType);
  }

  /**
   * Test label method inherited from base.
   */
  public function testLabel(): void {
    $label = $this->plugin->label();
    $this->assertEquals('Link', $label);
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
