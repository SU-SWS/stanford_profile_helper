<?php

namespace Drupal\stanford_layout_paragraphs\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\stanford_layout_paragraphs\Controller\SuChooseComponentController;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 *
 * @codeCoverageIgnore Difficult to test.
 */
class StanfordLayoutParagraphsRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    $events = parent::getSubscribedEvents();
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -300];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('layout_paragraphs.builder.choose_component')) {
      $route->setDefault('_controller', SuChooseComponentController::class . '::list');
    }
  }

}
