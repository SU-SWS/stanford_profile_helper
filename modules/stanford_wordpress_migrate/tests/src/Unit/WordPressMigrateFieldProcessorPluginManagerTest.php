<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginManager;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for WordPressMigrateFieldProcessorPluginManager.
 */
class WordPressMigrateFieldProcessorPluginManagerTest extends UnitTestCase {

  /**
   * The plugin manager under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginManager
   */
  protected $pluginManager;

  /**
   * Mock cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cacheBackend;

  /**
   * Mock module handler.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->cacheBackend = $this->createMock(CacheBackendInterface::class);
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);

    $namespaces = new \ArrayIterator();

    $this->pluginManager = new WordPressMigrateFieldProcessorPluginManager(
      $namespaces,
      $this->cacheBackend,
      $this->moduleHandler
    );
  }

  /**
   * Test plugin manager construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(WordPressMigrateFieldProcessorPluginManager::class, $this->pluginManager);
  }

  /**
   * Test getFieldPlugin method with matching field type.
   */
  public function testGetFieldPluginWithMatch(): void {
    // Create a partial mock to override getDefinitions and createInstance.
    $pluginManager = $this->getMockBuilder(WordPressMigrateFieldProcessorPluginManager::class)
      ->setConstructorArgs([
        new \ArrayIterator(),
        $this->cacheBackend,
        $this->moduleHandler,
      ])
      ->onlyMethods(['getDefinitions', 'createInstance'])
      ->getMock();

    // Mock plugin definitions.
    $pluginManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([
        'string' => [
          'id' => 'string',
          'label' => 'String',
          'fieldType' => ['string', 'string_long', 'email'],
        ],
        'text' => [
          'id' => 'text',
          'label' => 'Text',
          'fieldType' => ['text', 'text_long', 'text_with_summary'],
        ],
      ]);

    // Mock the plugin instance.
    $mockPlugin = $this->createMock(WordPressMigrateFieldProcessorInterface::class);
    $pluginManager->expects($this->once())
      ->method('createInstance')
      ->with('string')
      ->willReturn($mockPlugin);

    // Test that it finds and returns the plugin for a matching field type.
    $result = $pluginManager->getFieldPlugin('email');
    $this->assertInstanceOf(WordPressMigrateFieldProcessorInterface::class, $result);
    $this->assertSame($mockPlugin, $result);
  }

  /**
   * Test getFieldPlugin returns first matching plugin when multiple match.
   */
  public function testGetFieldPluginReturnsFirstMatch(): void {
    // Create a partial mock to override getDefinitions and createInstance.
    $pluginManager = $this->getMockBuilder(WordPressMigrateFieldProcessorPluginManager::class)
      ->setConstructorArgs([
        new \ArrayIterator(),
        $this->cacheBackend,
        $this->moduleHandler,
      ])
      ->onlyMethods(['getDefinitions', 'createInstance'])
      ->getMock();

    // Mock plugin definitions where 'string' appears in both.
    $pluginManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([
        'first_plugin' => [
          'id' => 'first_plugin',
          'label' => 'First Plugin',
          'fieldType' => ['string', 'email'],
        ],
        'second_plugin' => [
          'id' => 'second_plugin',
          'label' => 'Second Plugin',
          'fieldType' => ['string', 'text'],
        ],
      ]);

    // Mock the plugin instance - should only create the first one.
    $mockPlugin = $this->createMock(WordPressMigrateFieldProcessorInterface::class);
    $pluginManager->expects($this->once())
      ->method('createInstance')
      ->with('first_plugin')
      ->willReturn($mockPlugin);

    // Test that it returns the first matching plugin.
    $result = $pluginManager->getFieldPlugin('string');
    $this->assertInstanceOf(WordPressMigrateFieldProcessorInterface::class, $result);
  }

  /**
   * Test getFieldPlugin method with no matching field type.
   */
  public function testGetFieldPluginWithNoMatch(): void {
    // Create a partial mock to override getDefinitions.
    $pluginManager = $this->getMockBuilder(WordPressMigrateFieldProcessorPluginManager::class)
      ->setConstructorArgs([
        new \ArrayIterator(),
        $this->cacheBackend,
        $this->moduleHandler,
      ])
      ->onlyMethods(['getDefinitions', 'createInstance'])
      ->getMock();

    // Mock plugin definitions.
    $pluginManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([
        'string' => [
          'id' => 'string',
          'label' => 'String',
          'fieldType' => ['string', 'string_long'],
        ],
        'text' => [
          'id' => 'text',
          'label' => 'Text',
          'fieldType' => ['text', 'text_long'],
        ],
      ]);

    // createInstance should never be called.
    $pluginManager->expects($this->never())
      ->method('createInstance');

    // Test that it returns null when no matching field type is found.
    $result = $pluginManager->getFieldPlugin('non_existent_field_type');
    $this->assertNull($result);
  }

  /**
   * Test getFieldPlugin method with empty definitions.
   */
  public function testGetFieldPluginWithEmptyDefinitions(): void {
    // Create a partial mock to override getDefinitions.
    $pluginManager = $this->getMockBuilder(WordPressMigrateFieldProcessorPluginManager::class)
      ->setConstructorArgs([
        new \ArrayIterator(),
        $this->cacheBackend,
        $this->moduleHandler,
      ])
      ->onlyMethods(['getDefinitions', 'createInstance'])
      ->getMock();

    // Mock empty plugin definitions.
    $pluginManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([]);

    // createInstance should never be called.
    $pluginManager->expects($this->never())
      ->method('createInstance');

    // Test that it returns null when there are no definitions.
    $result = $pluginManager->getFieldPlugin('any_field_type');
    $this->assertNull($result);
  }

  /**
   * Test getFieldPlugin with field type in middle of array.
   */
  public function testGetFieldPluginWithFieldTypeInMiddleOfArray(): void {
    // Create a partial mock to override getDefinitions and createInstance.
    $pluginManager = $this->getMockBuilder(WordPressMigrateFieldProcessorPluginManager::class)
      ->setConstructorArgs([
        new \ArrayIterator(),
        $this->cacheBackend,
        $this->moduleHandler,
      ])
      ->onlyMethods(['getDefinitions', 'createInstance'])
      ->getMock();

    // Mock plugin definitions with field type in middle of array.
    $pluginManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([
        'string' => [
          'id' => 'string',
          'label' => 'String',
          'fieldType' => ['string', 'string_long', 'email', 'telephone'],
        ],
      ]);

    // Mock the plugin instance.
    $mockPlugin = $this->createMock(WordPressMigrateFieldProcessorInterface::class);
    $pluginManager->expects($this->once())
      ->method('createInstance')
      ->with('string')
      ->willReturn($mockPlugin);

    // Test that it finds field type in middle of array.
    $result = $pluginManager->getFieldPlugin('email');
    $this->assertInstanceOf(WordPressMigrateFieldProcessorInterface::class, $result);
  }

  /**
   * Test getFieldPlugin iterates through multiple definitions.
   */
  public function testGetFieldPluginIteratesThroughDefinitions(): void {
    // Create a partial mock to override getDefinitions and createInstance.
    $pluginManager = $this->getMockBuilder(WordPressMigrateFieldProcessorPluginManager::class)
      ->setConstructorArgs([
        new \ArrayIterator(),
        $this->cacheBackend,
        $this->moduleHandler,
      ])
      ->onlyMethods(['getDefinitions', 'createInstance'])
      ->getMock();

    // Mock plugin definitions - target is in the third definition.
    $pluginManager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn([
        'string' => [
          'id' => 'string',
          'label' => 'String',
          'fieldType' => ['string', 'string_long'],
        ],
        'text' => [
          'id' => 'text',
          'label' => 'Text',
          'fieldType' => ['text', 'text_long'],
        ],
        'number' => [
          'id' => 'number',
          'label' => 'Number',
          'fieldType' => ['integer', 'decimal', 'float'],
        ],
      ]);

    // Mock the plugin instance.
    $mockPlugin = $this->createMock(WordPressMigrateFieldProcessorInterface::class);
    $pluginManager->expects($this->once())
      ->method('createInstance')
      ->with('number')
      ->willReturn($mockPlugin);

    // Test that it iterates through all definitions to find the match.
    $result = $pluginManager->getFieldPlugin('decimal');
    $this->assertInstanceOf(WordPressMigrateFieldProcessorInterface::class, $result);
  }

}
