<?php

namespace Drupal\stanford_intranet\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\Core\State\StateInterface;
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
  public static function getSubscribedEvents(): array  {
    $events = parent::getSubscribedEvents();

    // Use a lower priority than \Drupal\views\EventSubscriber\RouteSubscriber
    // to ensure the requirement will be added to its routes.
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];
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
  protected function alterRoutes(RouteCollection $collection) {
    if ($this->state->get('stanford_intranet')) {
      $collection->get('xmlsitemap.sitemap_xml')?->setRequirement('_access', 'FALSE');
      $collection->get('xmlsitemap.sitemap_xsl')?->setRequirement('_access', 'FALSE');
    }
  }

}
