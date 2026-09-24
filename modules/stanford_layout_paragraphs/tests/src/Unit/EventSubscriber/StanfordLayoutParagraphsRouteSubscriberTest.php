<?php

namespace Drupal\Tests\stanford_layout_paragraphs\Unit\EventSubscriber;

use Drupal\Core\Routing\RouteBuildEvent;
use Drupal\Core\Routing\RoutingEvents;
use Drupal\stanford_layout_paragraphs\Controller\SuChooseComponentController;
use Drupal\stanford_layout_paragraphs\EventSubscriber\StanfordLayoutParagraphsRouteSubscriber;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Test the layout paragraphs route subscriber.
 */
#[Group('stanford_layout_paragraphs')]
class StanfordLayoutParagraphsRouteSubscriberTest extends UnitTestCase {

  /**
   * The subscriber runs late in the route alter event.
   */
  public function testSubscribedEvents(): void {
    $events = StanfordLayoutParagraphsRouteSubscriber::getSubscribedEvents();
    $this->assertEquals(['onAlterRoutes', -300], $events[RoutingEvents::ALTER]);
  }

  /**
   * The choose component route uses the overridden controller.
   */
  public function testAlterRoutes(): void {
    $route = new Route('/layout-paragraphs-builder/{layout_paragraphs_layout}/choose-component', [
      '_controller' => 'Drupal\layout_paragraphs\Controller\ChooseComponentController::list',
    ]);
    $other_route = new Route('/foo', ['_controller' => 'FooController::build']);
    $collection = new RouteCollection();
    $collection->add('layout_paragraphs.builder.choose_component', $route);
    $collection->add('foo', $other_route);

    $subscriber = new StanfordLayoutParagraphsRouteSubscriber();
    $subscriber->onAlterRoutes(new RouteBuildEvent($collection));

    $this->assertEquals(SuChooseComponentController::class . '::list', $route->getDefault('_controller'));
    $this->assertEquals('FooController::build', $other_route->getDefault('_controller'));
  }

  /**
   * Nothing breaks when layout paragraphs routes are not available.
   */
  public function testAlterRoutesMissingRoute(): void {
    $collection = new RouteCollection();
    $subscriber = new StanfordLayoutParagraphsRouteSubscriber();
    $subscriber->onAlterRoutes(new RouteBuildEvent($collection));
    $this->assertCount(0, $collection);
  }

}
