<?php

namespace Drupal\stanford_intranet\EventSubscriber;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\State\StateInterface;
use Drupal\search_api\Event\IndexingItemsEvent;
use Drupal\search_api\Event\SearchApiEvents;
use Drupal\stanford_intranet\Plugin\Field\FieldType\EntityAccessFieldType;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
class IntranetRouteSubscriber extends RouteSubscriberBase {

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();

    // Use a lower priority than \Drupal\views\EventSubscriber\RouteSubscriber
    // to ensure the requirement will be added to its routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];
    $events[SearchApiEvents::INDEXING_ITEMS] = 'alterSearchItems';
    return $events;
  }

  /**
   * Constructs an IntranetRouteSubscriber object.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(StateInterface $state) {
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($this->state->get('stanford_intranet')) {
      $collection->get('xmlsitemap.sitemap_xml')?->setRequirement('_access', 'FALSE');
      $collection->get('xmlsitemap.sitemap_xsl')?->setRequirement('_access', 'FALSE');
    }
  }

  /**
   * Alter the Search_API index items.
   *
   * @param \Drupal\search_api\Event\IndexingItemsEvent $event
   *   Search Api event.
   */
  public function alterSearchItems(IndexingItemsEvent $event) {
    $index = $event->getIndex();
    $items = $event->getItems();

    if (
      !$this->state->get('stanford_intranet', FALSE) ||
      !str_contains($index->id(), 'algolia')
    ) {
      return;
    }

    // Remove items that have restricted access.
    foreach ($items as $key => $item) {
      $entity = $item->getOriginalObject()->getValue();
      if (!$entity instanceof ContentEntityInterface || !$entity->hasField(EntityAccessFieldType::FIELD_NAME)) {
        continue;
      }

      $access_settings = $entity->get(EntityAccessFieldType::FIELD_NAME)
        ->getValue();
      if (!empty($access_settings)) {
        unset($items[$key]);
      }
    }
    $event->setItems($items);
  }

}
