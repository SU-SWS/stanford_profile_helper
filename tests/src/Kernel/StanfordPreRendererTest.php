<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_profile_helper\StanfordPreRenderer;

/**
 * Class StanfordPreRendererTest.
 *
 * @group stanford_profile_helper
 */
class StanfordPreRendererTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'contextual'];

  /**
   * Trusted callbacks exist.
   */
  public function testTrustedCallbacks() {
    $callbacks = StanfordPreRenderer::trustedCallbacks();
    $this->assertTrue(in_array('preRenderDsEntity', $callbacks));
  }

  /**
   * Contextual links get added to the ds entity display.
   */
  public function testDsEntity() {
    $element = ['#contextual_links' => []];

    $output = \Drupal::service('renderer')
      ->executeInRenderContext(new RenderContext(), function () use ($element) {
        return $modified = StanfordPreRenderer::preRenderDsEntity($element);
      });
    $this->assertArrayHasKey('#prefix', $output);
    $this->assertStringContainsString('data-contextual-id', (string) $output['#prefix']);
  }

}
