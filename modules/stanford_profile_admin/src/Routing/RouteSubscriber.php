<?php

namespace Drupal\stanford_profile_admin\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\stanford_profile_admin\Routing
 */
class RouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritDoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    foreach ($collection as $route) {
      if (str_starts_with($route->getPath(), '/admin/people')) {
        $route->setPath(str_replace('/admin/people', '/admin/users', $route->getPath()));
      }
    }
    if ($route = $collection->get('entity.user.collection')) {
      $route->setDefault('_title', 'Users');
    }
    if ($route = $collection->get('ui_patterns.patterns.overview')) {
      $route->setPath('/admin/patterns');
      $route->setOption('_admin_route', FALSE);
    }
  }

}
