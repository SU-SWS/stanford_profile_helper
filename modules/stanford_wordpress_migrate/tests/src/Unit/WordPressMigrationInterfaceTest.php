<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for WordPressMigrationInterface.
 */
class WordPressMigrationInterfaceTest extends UnitTestCase {

  /**
   * Test the interface extends expected interfaces.
   */
  public function testInterfaceExtends(): void {
    $this->assertTrue(interface_exists(WordPressMigrationInterface::class));

    $reflection = new \ReflectionClass(WordPressMigrationInterface::class);
    $interfaces = $reflection->getInterfaceNames();

    $this->assertContains(ContentEntityInterface::class, $interfaces);
    $this->assertContains(EntityPublishedInterface::class, $interfaces);
  }

  /**
   * Test the interface defines the expected methods.
   */
  public function testInterfaceMethods(): void {
    $reflection = new \ReflectionClass(WordPressMigrationInterface::class);

    $expectedMethods = [
      'enable',
      'disable',
      'getBaseUrl',
      'getConfiguration',
      'setConfigurationValue',
      'getConfigurationValue',
    ];

    foreach ($expectedMethods as $methodName) {
      $this->assertTrue($reflection->hasMethod($methodName), "Interface should have method: $methodName");
    }
  }

  /**
   * Test method signatures.
   */
  public function testMethodSignatures(): void {
    $reflection = new \ReflectionClass(WordPressMigrationInterface::class);

    // Test enable method signature
    $enableMethod = $reflection->getMethod('enable');
    $this->assertTrue($enableMethod->hasReturnType());

    // Test disable method signature
    $disableMethod = $reflection->getMethod('disable');
    $this->assertTrue($disableMethod->hasReturnType());

    // Test getBaseUrl method signature
    $getBaseUrlMethod = $reflection->getMethod('getBaseUrl');
    $returnType = $getBaseUrlMethod->getReturnType();
    $this->assertNotNull($returnType);
    $this->assertTrue($returnType->allowsNull());

    // Test getConfiguration method signature
    $getConfigurationMethod = $reflection->getMethod('getConfiguration');
    $this->assertEquals('array', (string) $getConfigurationMethod->getReturnType());

    // Test setConfigurationValue method signature
    $setConfigurationValueMethod = $reflection->getMethod('setConfigurationValue');
    $this->assertEquals('void', (string) $setConfigurationValueMethod->getReturnType());
    $parameters = $setConfigurationValueMethod->getParameters();
    $this->assertCount(2, $parameters);

    // Test getConfigurationValue method signature
    $getConfigurationValueMethod = $reflection->getMethod('getConfigurationValue');
    $parameters = $getConfigurationValueMethod->getParameters();
    $this->assertCount(2, $parameters);
    $this->assertTrue($parameters[1]->isDefaultValueAvailable());
    $this->assertNull($parameters[1]->getDefaultValue());
  }

}
