<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Plugin\WordPressMigrateFieldProcessor;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\EntityReferenceField;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for EntityReferenceField plugin.
 */
class EntityReferenceFieldTest extends UnitTestCase {

  /**
   * The plugin under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Plugin\WordPressMigrateFieldProcessor\EntityReferenceField
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

    $this->plugin = new EntityReferenceField(['migration' => $this->migration], 'entity_reference', [
      'label' => 'Entity Reference',
      'fieldType' => ['entity_reference'],
    ]);
  }

  /**
   * Test plugin construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(EntityReferenceField::class, $this->plugin);
  }

  /**
   * Test getProcess method with taxonomy term reference.
   */
  public function testGetProcessTaxonomyTerm(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $field->method('getSetting')
      ->willReturnMap([
        ['target_type', 'taxonomy_term'],
        ['handler_settings', ['target_bundles' => ['category']]],
      ]);

    $this->migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('taxonomy_term', [])
      ->willReturn(['wp-json/wp/v2/categories' => ['category' => []]]);

    $this->migration->expects($this->never())
      ->method('id');

    $process = $this->plugin->getProcess($field);

    $this->assertIsArray($process);
    $this->assertCount(2, $process);
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);
    $this->assertEquals('migration_lookup', $process[1]['plugin']);
    $this->assertArrayHasKey('migration', $process[1]);
    $this->assertArrayHasKey('stub_id', $process[1]);
  }

  /**
   * Test getProcess method with media reference.
   */
  public function testGetProcessMedia(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $field->method('getSetting')
      ->willReturnMap([
        ['target_type', 'media'],
        ['handler_settings', ['target_bundles' => []]],
      ]);

    // For media references, getPossibleTermMigrations is called and returns empty
    // which results in the fallback ['wordpress_media:media']
    $this->migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('taxonomy_term', [])
      ->willReturn([]);

    $process = $this->plugin->getProcess($field);

    $this->assertIsArray($process);
    $this->assertCount(2, $process);
    $this->assertEquals('skip_on_empty', $process[0]['plugin']);
    $this->assertEquals('migration_lookup', $process[1]['plugin']);
    $this->assertEquals(['wordpress_media:media'], $process[1]['migration']);
    $this->assertEquals('wordpress_media:media', $process[1]['stub_id']);
  }

  /**
   * Test getProcess method with unsupported entity reference.
   */
  public function testGetProcessUnsupportedReference(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $field->method('getSetting')
      ->willReturnMap([
        ['target_type', 'node'],
        ['handler_settings', []],
      ]);

    $process = $this->plugin->getProcess($field);

    $this->assertEquals([], $process);
  }

  /**
   * Test getPossibleTermMigrations method.
   */
  public function testGetPossibleTermMigrations(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getSetting')
      ->with('handler_settings')
      ->willReturn(['target_bundles' => ['category', 'tags']]);

    $this->migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('taxonomy_term', [])
      ->willReturn([
        'wp-json/wp/v2/categories' => ['category' => []],
        'wp-json/wp/v2/tags' => ['tags' => []],
        'wp-json/wp/v2/other' => ['other' => []],
      ]);

    $this->migration->expects($this->never())
      ->method('id');

    $reflection = new \ReflectionClass($this->plugin);
    $method = $reflection->getMethod('getPossibleTermMigrations');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->plugin, $field);

    $this->assertIsArray($result);
    $this->assertCount(2, $result);
    $this->assertContains('wordpress_terms:categories__category', $result);
    $this->assertContains('wordpress_terms:tags__tags', $result);
  }

  /**
   * Test getPossibleTermMigrations method with empty target bundles.
   */
  public function testGetPossibleTermMigrationsEmptyTargetBundles(): void {
    $field = $this->createMock(FieldDefinitionInterface::class);

    $field->expects($this->once())
      ->method('getSetting')
      ->with('handler_settings')
      ->willReturn(['target_bundles' => NULL]);

    $this->migration->expects($this->once())
      ->method('getConfigurationValue')
      ->with('taxonomy_term', [])
      ->willReturn(['wp-json/wp/v2/categories' => ['category' => []]]);

    $reflection = new \ReflectionClass($this->plugin);
    $method = $reflection->getMethod('getPossibleTermMigrations');
    $method->setAccessible(TRUE);

    $result = $method->invoke($this->plugin, $field);

    $this->assertEquals([], $result);
  }

}
