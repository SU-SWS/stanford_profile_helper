<?php

namespace Drupal\Tests\stanford_decoupled\Kernel;

use Drupal\KernelTests\KernelTestBase;

class StanfordDecoupledServiceProviderTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'stanford_decoupled',
    'basic_auth',
    'user',
  ];

  public function testBasicAuthService() {
    $definition = $this->container->getDefinition('basic_auth.authentication.basic_auth');
    $tags = $definition->getTag('authentication_provider');
    $this->assertTrue($tags[0]['global']);
  }

}
