<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_wordpress_migrate\Entity\WordPressMigration;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for WordPressMigration entity.
 */
#[RunTestsInSeparateProcesses]
class WordPressMigrationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'user',
    'migrate',
    'stanford_wordpress_migrate',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('wordpress_migration');
  }

  /**
   * Test entity creation.
   */
  public function testEntityCreation(): void {
    $entity = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);
    $entity->save();

    $this->assertInstanceOf(WordPressMigrationInterface::class, $entity);
    $this->assertEquals('Test Migration', $entity->label());
    $this->assertEquals('https://example.com', $entity->getBaseUrl());
  }

  /**
   * Test entity save and load.
   */
  public function testEntitySaveAndLoad(): void {
    $cache = $this->container->get('cache.discovery_migration');
    /** @var \Drupal\migrate\Plugin\MigrationPluginManager $pluginManager */
    $pluginManager = $this->container->get('plugin.manager.migration');
    $pluginManager->getDefinitions();
    $cachedPlugins = $cache->get('migration_plugins');
    $this->assertNotEmpty($cachedPlugins);

    $entity = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_urlplugin.manager.migration:' => 'https://example.com',
    ]);
    $entity->save();
    $cachedPlugins = $cache->get('migration_plugins');
    $this->assertEmpty($cachedPlugins);

    $pluginManager->getDefinitions();
    $cachedPlugins = $this->container->get('cache.discovery_migration')
      ->get('migration_plugins');
    $this->assertNotEmpty($cachedPlugins);

    $entity->delete();
    $cachedPlugins = $cache->get('migration_plugins');
    $this->assertEmpty($cachedPlugins);
  }

  /**
   * Test enable and disable methods.
   */
  public function testEnableDisable(): void {
    $entity = WordPressMigration::create([
      'label' => 'Test Migration',
    ]);

    // Test enable
    $result = $entity->enable();
    $this->assertSame($entity, $result);
    $this->assertTrue($entity->isPublished());

    // Test disable
    $result = $entity->disable();
    $this->assertSame($entity, $result);
    $this->assertFalse($entity->isPublished());
  }

  /**
   * Test getBaseUrl method.
   */
  public function testGetBaseUrl(): void {
    $entity = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => '', // Empty string, not NULL
    ]);

    // Empty string should be returned as empty string, not null
    $this->assertEquals('', $entity->getBaseUrl());

    $entity->set('base_url', 'https://example.com');
    $this->assertEquals('https://example.com', $entity->getBaseUrl());
  }

  /**
   * Test configuration methods.
   */
  public function testConfiguration(): void {
    $entity = WordPressMigration::create([
      'label' => 'Test Migration',
    ]);

    // Test initial empty configuration
    $config = $entity->getConfiguration();
    $this->assertEquals([], $config);

    // Test setting configuration value
    $entity->setConfigurationValue('test_key', 'test_value');
    $this->assertEquals('test_value', $entity->getConfigurationValue('test_key'));

    // Test nested configuration
    $entity->setConfigurationValue(['nested', 'key'], 'nested_value');
    $this->assertEquals('nested_value', $entity->getConfigurationValue([
      'nested',
      'key',
    ]));

    // Test default value
    $this->assertEquals('default', $entity->getConfigurationValue('nonexistent', 'default'));

    // Test full configuration
    $config = $entity->getConfiguration();
    $this->assertArrayHasKey('test_key', $config);
    $this->assertArrayHasKey('nested', $config);
    $this->assertEquals('test_value', $config['test_key']);
    $this->assertEquals(['key' => 'nested_value'], $config['nested']);
  }

  /**
   * Test entity fields and base field definitions.
   */
  public function testBaseFields(): void {
    $entity_type = \Drupal::entityTypeManager()
      ->getDefinition('wordpress_migration');
    $base_fields = WordPressMigration::baseFieldDefinitions($entity_type);

    $this->assertArrayHasKey('id', $base_fields);
    $this->assertArrayHasKey('label', $base_fields);
    $this->assertArrayHasKey('base_url', $base_fields);
    $this->assertArrayHasKey('configuration', $base_fields);
    $this->assertArrayHasKey('status', $base_fields); // From publishedBaseFieldDefinitions
    // Note: created/changed fields are added by parent::baseFieldDefinitions() if the entity type supports them
  }

}
