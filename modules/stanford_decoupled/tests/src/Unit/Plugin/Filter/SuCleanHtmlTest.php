<?php

namespace Drupal\Tests\stanford_decoupled\Unit\Plugin\Filter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\stanford_decoupled\Plugin\Filter\SuCleanHtml;

/**
 * Class SulCleanHtmlTest.
 */
class SuCleanHtmlTest extends UnitTestCase {

  /**
   * Test the clean html filter.
   */
  public function testFilter() {
    $config = [];
    $definition = ['provider' => 'stanford_profile_helper'];

    $entity_query = $this->createMock(QueryInterface::class);
    $entity_query->method('accessCheck')->willReturnSelf();
    $entity_query->method('count')->willReturnSelf();
    $entity_query->method('execute')->willReturn(1);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('getQuery')->willReturn($entity_query);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);

    $filter = SuCleanHtml::create($container, $config, 'foo', $definition);
    $result = $filter->process("\r\n<!-- FOO BAR BAZ-->\n\n<div>foo</div>\n\n\n<div>\r\nbar\r\n\r\nbaz</div>\r\n", NULL);

    $this->assertEquals('<div>foo</div><div> bar baz</div>', (string) $result);
  }

}
