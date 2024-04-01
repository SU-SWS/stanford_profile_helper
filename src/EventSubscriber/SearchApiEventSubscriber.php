<?php

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\node\NodeInterface;
use Drupal\search_api\Event\IndexingItemsEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * SearchApi event subscriber service.
 */
class SearchApiEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $events = [
      SearchApiEvents::INDEXING_ITEMS => 'alterIndexItems',
    ];

    return $events;
  }

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory) {}

  /**
   * Alter the Search_API index items.
   *
   * @param \Drupal\search_api\Event\IndexingItemsEvent $event
   *   Search Api event.
   */
  public function alterIndexItems(IndexingItemsEvent $event) {
    $exclude_pages = $this->configFactory->get('system.site')->get('page');
    unset($exclude_pages['front']);
    $items = $event->getItems();

    // Remove the 404, and 403 from indexing for search results.
    foreach ($items as $item_id => $item) {
      $entity = $item->getOriginalObject()->getValue();
      if ($entity instanceof NodeInterface) {
        if (in_array('/node/' . $entity->id(), $exclude_pages)) {
          unset($items[$item_id]);
        }
      }
    }
    $event->setItems($items);
  }

}
