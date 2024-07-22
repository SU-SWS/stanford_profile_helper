<?php

namespace Drupal\stanford_profile_helper;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * Modifies the language manager service.
 */
class StanfordProfileHelperServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    if ($container->hasDefinition('search_api_algolia.helper')) {
      $definition = $container->getDefinition('search_api_algolia.helper');
      $definition->setClass('Drupal\stanford_profile_helper\SearchApiAlgoliaHelper');
    }
  }

}
