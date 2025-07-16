<?php

namespace Drupal\Tests\stanford_decoupled\Kernel\EventSubscriber;

use Drupal\KernelTests\KernelTestBase;

class StanfordDecoupledRouteSubscriberTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'basic_auth',
    'stanford_decoupled',
  ];

  public function testRouteAlter() {
    /** @var \Drupal\Core\Routing\AccessAwareRouter $router */
    $router = \Drupal::service('router');
    $this->assertEmpty($router->getRouteCollection()->get('system.files')->getOption('_auth'));
    $this->assertEmpty($router->getRouteCollection()->get('system.private_file_download')->getOption('_auth'));

    \Drupal::cache()->set('stanford_decoupled', true);
    $this->assertEmpty($router->getRouteCollection()->get('system.files')->getOption('_auth'));
    $this->assertEmpty($router->getRouteCollection()->get('system.private_file_download')->getOption('_auth'));
  }

}
