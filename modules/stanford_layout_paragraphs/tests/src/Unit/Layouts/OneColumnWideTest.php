<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_layout_paragraphs\Unit\Layouts;

use Drupal\stanford_layout_paragraphs\Layouts\OneColumnWide;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for OneColumnWide layout.
 */
#[CoversClass(OneColumnWide::class)]
class OneColumnWideTest extends UnitTestCase {

  /**
   * Test that OneColumnWide extends LayoutDefault.
   */
  public function testOneColumnWideExtendsLayoutDefault(): void {
    $configuration = [];
    $plugin_id = 'one_column_wide';
    $plugin_definition = [
      'label' => 'One Column Wide',
      'category' => 'Test',
      'template' => 'templates/one-column-wide',
      'regions' => [
        'main' => ['label' => 'Main'],
      ],
    ];

    $layout = new OneColumnWide($configuration, $plugin_id, $plugin_definition);

    $this->assertInstanceOf('\Drupal\Core\Layout\LayoutDefault', $layout);
  }

  /**
   * Test that OneColumnWide can be instantiated.
   */
  public function testOneColumnWideInstantiation(): void {
    $configuration = [];
    $plugin_id = 'one_column_wide';
    $plugin_definition = [
      'label' => 'One Column Wide',
      'category' => 'Test',
      'template' => 'templates/one-column-wide',
      'regions' => [
        'main' => ['label' => 'Main'],
      ],
    ];

    $layout = new OneColumnWide($configuration, $plugin_id, $plugin_definition);

    $this->assertNotNull($layout);
    $this->assertEquals('one_column_wide', $layout->getPluginId());
  }

}
