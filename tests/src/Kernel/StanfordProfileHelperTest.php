<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel;

use Drupal\Core\Render\RenderContext;
use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_profile_helper\StanfordProfileHelper;

/**
 * Class StanfordProfileHelperTest.
 */
class StanfordProfileHelperTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['system', 'contextual'];

  public function testCacheTagRemoval() {
    $variable = [];
    StanfordProfileHelper::removeCacheTags($variable, ['^foo$']);
    $this->assertEquals([], $variable);

    $variable = ['#cache' => ['tags' => []]];
    StanfordProfileHelper::removeCacheTags($variable, ['^foo$']);
    $this->assertEquals(['#cache' => ['tags' => []]], $variable);

    $variable = ['#cache' => ['tags' => ['bar']]];
    StanfordProfileHelper::removeCacheTags($variable, ['^foo$']);
    $this->assertEquals(['#cache' => ['tags' => ['bar']]], $variable);

    $variable = ['#cache' => ['tags' => ['foo','bar']]];
    StanfordProfileHelper::removeCacheTags($variable, ['^foo$']);
    $this->assertEquals(['#cache' => ['tags' => ['bar']]], $variable);

    $variable = ['#cache' => ['tags' => ['foo:bar','bar']]];
    StanfordProfileHelper::removeCacheTags($variable, ['^foo$']);
    $this->assertEquals(['#cache' => ['tags' => ['foo:bar', 'bar']]], $variable);

    $variable = ['#cache' => ['tags' => ['foo:bar','bar']]];
    StanfordProfileHelper::removeCacheTags($variable, ['foo']);
    $this->assertEquals(['#cache' => ['tags' => ['bar']]], $variable);

    $variable = ['#cache' => ['tags' => ['foo:bar','bar']]];
    StanfordProfileHelper::removeCacheTags($variable, ['bar']);
    $this->assertEquals(['#cache' => ['tags' => []]], $variable);
  }

}
