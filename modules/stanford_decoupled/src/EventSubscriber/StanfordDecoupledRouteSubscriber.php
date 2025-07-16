<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\EventSubscriber;

use Drupal\Core\Routing\RouteSubscriberBase;
use Drupal\stanford_decoupled\Config\DecoupledConfigOverrides;
use Symfony\Component\Routing\RouteCollection;

/**
 * Route subscriber.
 */
final class StanfordDecoupledRouteSubscriber extends RouteSubscriberBase {

  protected array $providerIds = [];

  /**
   * @param array $authentication_providers
   */
  public function __construct(array $authentication_providers) {
    $this->providerIds = array_keys($authentication_providers);
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection): void {
    if (!DecoupledConfigOverrides::isDecoupled()) {
      return;
    }

    if ($route = $collection->get('system.files')) {
      $route->addOptions(['_auth' => $this->providerIds]);
    }
    if ($route = $collection->get('system.private_file_download')) {
      $route->addOptions(['_auth' => $this->providerIds]);
    }
  }

}
