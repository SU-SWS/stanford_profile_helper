<?php

namespace Drupal\Tests\stanford_decoupled\Unit\Config;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\stanford_decoupled\Config\DecoupledConfigOverrides;
use Drupal\Tests\UnitTestCase;

/**
 * Class SulCleanHtmlTest.
 */
class DecoupledConfigOverridesTest extends UnitTestCase {

  protected $cacheData;

  protected $hasEntityDefinition = FALSE;

  protected $entityCount = 0;

  protected function setUp(): void {
    parent::setUp();

    $entity_query = $this->createMock(QueryInterface::class);
    $entity_query->method('accessCheck')->willReturnSelf();
    $entity_query->method('count')->willReturnSelf();
    $entity_query->method('execute')->willReturnReference($this->entityCount);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('getQuery')->willReturn($entity_query);

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturnReference($this->cacheData);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('hasDefinition')
      ->willReturnReference($this->hasEntityDefinition);
    $entity_type_manager->method('getStorage')
      ->willReturn($entity_storage);

    $container = new ContainerBuilder();
    $container->set('cache.default', $cache);
    $container->set('entity_type.manager', $entity_type_manager);
    \Drupal::setContainer($container);
  }

  public function testConfigOverrides() {
    $overrider = new DecoupledConfigOverrides();
    $overrides = $overrider->loadOverrides(['filter.format.html']);
    $this->assertEmpty($overrides);

    $this->hasEntityDefinition = TRUE;
    $overrides = $overrider->loadOverrides(['filter.format.html']);
    $this->assertEmpty($overrides);

    $this->entityCount = 1;
    $overrides = $overrider->loadOverrides(['filter.format.html']);
    $this->assertNotEmpty($overrides);

    $this->cacheData = new \stdClass();
    $this->cacheData->data = TRUE;
    $overrides = $overrider->loadOverrides(['filter.format.html']);
    $this->assertNotEmpty($overrides);
  }

}
