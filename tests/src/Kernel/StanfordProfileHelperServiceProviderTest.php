<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel;

use Drupal\stanford_profile_helper\SearchApiAlgoliaHelper;

/**
 */
class StanfordProfileHelperServiceProviderTest extends SuProfileHelperKernelTestBase {

  protected function setUp(): void {
    parent::setUp();
    $this->container->get('module_installer')->install(['search_api_algolia']);
  }

  public function testAlgoliaServiceReplaced() {
    $this->assertInstanceOf(SearchApiAlgoliaHelper::class, $this->container->get('search_api_algolia.helper'));
  }

}
