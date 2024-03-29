<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\Core\TypedData\ComplexDataInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\NodeInterface;
use Drupal\search_api\Event\IndexingItemsEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\ItemInterface;

/**
 * Test the event subscriber.
 *
 * @coversDefaultClass \Drupal\stanford_profile_helper\EventSubscriber\SearchApiEventSubscriber
 */
class SearchApiSubscriberTest extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'system',
    'search_api',
    'stanford_profile_helper',
    'rabbit_hole',
    'config_pages',
  ];

  public function testSearchItemsAlter() {
    $index = $this->createMock(IndexInterface::class);

    $node1 = $this->createMock(NodeInterface::class);
    $node1->method('id')->willReturn(123);
    $item1 = $this->createMock(ItemInterface::class);
    $object1 = $this->createMock(ComplexDataInterface::class);
    $object1->method('getValue')->willReturn($node1);
    $item1->method('getOriginalObject')->willReturn($object1);

    $node2 = $this->createMock(NodeInterface::class);
    $node2->method('id')->willReturn(234);
    $item2 = $this->createMock(ItemInterface::class);
    $object2 = $this->createMock(ComplexDataInterface::class);
    $object2->method('getValue')->willReturn($node2);
    $item2->method('getOriginalObject')->willReturn($object2);

    $node3 = $this->createMock(NodeInterface::class);
    $node3->method('id')->willReturn(345);
    $item3 = $this->createMock(ItemInterface::class);
    $object3 = $this->createMock(ComplexDataInterface::class);
    $object3->method('getValue')->willReturn($node3);
    $item3->method('getOriginalObject')->willReturn($object3);

    \Drupal::configFactory()
      ->getEditable('system.site')
      ->set('page', ['front' => '/node/123', '403' => '/node/234'])
      ->save();

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
