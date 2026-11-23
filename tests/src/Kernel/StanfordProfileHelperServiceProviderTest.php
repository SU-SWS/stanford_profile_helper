<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel;

use Drupal\stanford_profile_helper\SearchApiAlgoliaHelper;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the service provider replaces the Algolia helper service.
 */
#[Group('stanford_profile_helper')]
#[RunTestsInSeparateProcesses]
class StanfordProfileHelperServiceProviderTest extends SuProfileHelperKernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('module_installer')->install(['search_api_algolia']);
  }

  /**
   * Test the Algolia helper service is replaced with the module's class.
   */
  public function testAlgoliaServiceReplaced() {
    $this->assertInstanceOf(SearchApiAlgoliaHelper::class, $this->container->get('search_api_algolia.helper'));
  }

}
