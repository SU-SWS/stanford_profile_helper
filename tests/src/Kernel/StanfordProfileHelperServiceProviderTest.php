<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel;

use Drupal\stanford_profile_helper\SearchApiAlgoliaHelper;

/**
 * @coversDefaultClass \Drupal\stanford_profile_helper\SearchApiAlgoliaHelper
 */
class StanfordProfileHelperServiceProviderTest extends SuProfileHelperKernelTestBase {

  protected static $modules = [
    'config_pages',
    'core_event_dispatcher',
    'hook_event_dispatcher',
    'preprocess_event_dispatcher',
    'default_content',
    'node',
    'serialization',
    'stanford_profile_helper',
    'system',
    'user',
    'path_alias',
    'rabbit_hole',
    'rh_node',
    'menu_link_content',
    'redirect',
    'text',
    'field',
    'field_ui',
    'config_pages',
    'link',
    'taxonomy',
    'pathauto',
    'token',
    'paragraphs',
    'options',
    'file',
    'next',
    'search_api_algolia'
  ];

  public function testAlgoliaServiceReplaced() {
    $this->assertInstanceOf(SearchApiAlgoliaHelper::class, $this->container->get('search_api_algolia.helper'));
  }

}
