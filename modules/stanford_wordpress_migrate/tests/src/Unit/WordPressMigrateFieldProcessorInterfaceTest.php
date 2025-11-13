<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for WordPressMigrateFieldProcessorInterface.
 */
class WordPressMigrateFieldProcessorInterfaceTest extends UnitTestCase {

  /**
   * Test the interface defines the expected methods.
   */
  public function testInterfaceMethods(): void {
    $this->assertTrue(interface_exists(WordPressMigrateFieldProcessorInterface::class));

    $reflection = new \ReflectionClass(WordPressMigrateFieldProcessorInterface::class);

    $expectedMethods = [
      'label',
      'getFieldColumns',
      'getConstants',
      'getProcess',
      'getExtraProcess',
      'getMultiplePlugin',
      'setWordPressMigration',
    ];

    foreach ($expectedMethods as $methodName) {
      $this->assertTrue($reflection->hasMethod($methodName), "Interface should have method: $methodName");
    }
  }

  /**
   * Test method signatures.
   */
  public function testMethodSignatures(): void {
    $reflection = new \ReflectionClass(WordPressMigrateFieldProcessorInterface::class);

    // Test label method signature
    $labelMethod = $reflection->getMethod('label');
    $this->assertEquals('string', (string) $labelMethod->getReturnType());

    // Test getFieldColumns method signature
    $getFieldColumnsMethod = $reflection->getMethod('getFieldColumns');
    $this->assertEquals('array', (string) $getFieldColumnsMethod->getReturnType());
    $parameters = $getFieldColumnsMethod->getParameters();
    $this->assertCount(1, $parameters);
    $this->assertEquals(FieldDefinitionInterface::class, (string) $parameters[0]->getType());

    // Test getConstants method signature
    $getConstantsMethod = $reflection->getMethod('getConstants');
    $this->assertEquals('array', (string) $getConstantsMethod->getReturnType());

    // Test getProcess method signature
    $getProcessMethod = $reflection->getMethod('getProcess');
    $this->assertEquals('array', (string) $getProcessMethod->getReturnType());
    $parameters = $getProcessMethod->getParameters();
    $this->assertCount(2, $parameters);
    $this->assertEquals(FieldDefinitionInterface::class, (string) $parameters[0]->getType());
    $this->assertTrue($parameters[1]->allowsNull());

    // Test setWordPressMigration method signature
    $setWordPressMigrationMethod = $reflection->getMethod('setWordPressMigration');
    $this->assertTrue($setWordPressMigrationMethod->hasReturnType());
    $parameters = $setWordPressMigrationMethod->getParameters();
    $this->assertCount(1, $parameters);
    $this->assertEquals(WordPressMigrationInterface::class, (string) $parameters[0]->getType());
  }

}
