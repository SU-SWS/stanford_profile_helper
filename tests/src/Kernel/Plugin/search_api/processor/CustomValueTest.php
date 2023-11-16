<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\Plugin\search_api\processor;

use Drupal\Tests\search_api\Kernel\Processor\CustomValueTest as SearchApiCustomValueTest;

/**
 * @coversDefaultClass \Drupal\stanford_profile_helper\Plugin\search_api\processor\CustomValue
 */
class CustomValueTest extends SearchApiCustomValueTest {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'user',
    'node',
    'field',
    'search_api',
    'search_api_db',
    'search_api_test',
    'comment',
    'text',
    'action',
    'system',
    'stanford_profile_helper',
    'rabbit_hole',
    'config_pages',
  ];

  public function testPlugin() {
    /** @var \Drupal\search_api\Processor\ProcessorPluginManager $plugin_manager */
    $plugin_manager = \Drupal::service('plugin.manager.search_api.processor');
    $plugin = $plugin_manager->createInstance('custom_value');
    $this->assertInstanceOf('\Drupal\stanford_profile_helper\Plugin\search_api\processor\CustomValue', $plugin);
  }

}
