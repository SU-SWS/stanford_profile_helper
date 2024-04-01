<?php

namespace Drupal\Tests\stanford_intranet\Kernel\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\State\StateInterface;
use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;
use Drupal\search_api\Event\IndexingItemsEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\stanford_intranet\Plugin\Field\FieldType\EntityAccessFieldType;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
class IntranetRouteSubscriberTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'search_api',
    'stanford_intranet',
    'file',
  ];

  public function testSearchItemsAlter() {
    \Drupal::state()->set('stanford_intranet', 1);
    $index = $this->createMock(IndexInterface::class);
    $index->method('id')->willReturn('foo_algolia_bar');

    $node1 = $this->createMock(NodeInterface::class);
    $node1->method('hasField')->willReturn(FALSE);
    $item1 = $this->createMock(ItemInterface::class);
    $object1 = $this->createMock(ComplexDataInterface::class);
    $object1->method('getValue')->willReturn($node1);
    $item1->method('getOriginalObject')->willReturn($object1);

    $field_list2 = $this->createMock(FieldItemListInterface::class);
    $field_list2->method('getValue')->willReturn([]);
    $node2 = $this->createMock(NodeInterface::class);
    $node2->method('hasField')->willReturn(TRUE);
    $node2->method('get')->willReturn($field_list2);
    $item2 = $this->createMock(ItemInterface::class);
    $object2 = $this->createMock(ComplexDataInterface::class);
    $object2->method('getValue')->willReturn($node2);
    $item2->method('getOriginalObject')->willReturn($object2);

    $field_list3 = $this->createMock(FieldItemListInterface::class);
    $field_list3->method('getValue')->willReturn(['foo', 'bar']);
    $node3 = $this->createMock(NodeInterface::class);
    $node3->method('hasField')->willReturn(TRUE);
    $node3->method('get')->willReturn($field_list3);
    $item3 = $this->createMock(ItemInterface::class);
    $object3 = $this->createMock(ComplexDataInterface::class);
    $object3->method('getValue')->willReturn($node3);
    $item3->method('getOriginalObject')->willReturn($object3);

    $items = [
      $item1,
      $item2,
      $item3,
    ];
    $event = new IndexingItemsEvent($index, $items);

    $event_dispatcher = \Drupal::service('event_dispatcher');
    $event_dispatcher->dispatch($event, SearchApiEvents::INDEXING_ITEMS);

    $this->assertCount(2, $event->getItems());
  }

}
