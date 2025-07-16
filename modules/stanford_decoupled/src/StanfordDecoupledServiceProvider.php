<?php

namespace Drupal\stanford_decoupled;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Service provider for decoupled functionality.
 */
class StanfordDecoupledServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('basic_auth.authentication.basic_auth')) {
      // Sets the Basic Authentication provider as global.
      $basic_auth_definition = $container->getDefinition('basic_auth.authentication.basic_auth');
      $tags = $basic_auth_definition->getTags();
      $tags['authentication_provider'][0]['global'] = TRUE;
      $basic_auth_definition->setTags($tags);
    }
  }

}
