<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_profile_helper\StanfordProfileHelper;

/**
 * Class StanfordProfileHelperTest.
 *
 * @group stanford_profile_helper
 */
class StanfordProfileHelperTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'contextual'];

  /**
   * Trusted callbacks exist.
   */
  public function testTrustedCallbacks() {
    $callbacks = StanfordProfileHelper::trustedCallbacks();
    $this->assertTrue(in_array('preRenderDsEntity', $callbacks));
  }

  /**
   * Contextual links get added to the ds entity display.
   */
  public function testDsEntity() {
    $element = ['#contextual_links' => []];

    $output = \Drupal::service('renderer')
      ->executeInRenderContext(new RenderContext(), function () use ($element) {
        return StanfordProfileHelper::preRenderDsEntity($element);
      });
    $this->assertArrayHasKey('#prefix', $output);
    $this->assertStringContainsString('data-contextual-id', (string) $output['#prefix']);
  }

}
