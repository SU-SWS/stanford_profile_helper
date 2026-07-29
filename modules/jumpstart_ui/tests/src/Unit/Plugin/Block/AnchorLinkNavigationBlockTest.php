<?php

declare(strict_types=1);

namespace Drupal\Tests\jumpstart_ui\Unit\Plugin\Block;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormState;
use Drupal\Tests\UnitTestCase;
use Drupal\jumpstart_ui\Plugin\Block\AnchorLinkNavigationBlock;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for AnchorLinkNavigationBlock.
 */
#[Group('jumpstart_ui')]
#[CoversClass(AnchorLinkNavigationBlock::class)]
class AnchorLinkNavigationBlockTest extends UnitTestCase {

  /**
   * The block plugin.
   *
   * @var \Drupal\jumpstart_ui\Plugin\Block\AnchorLinkNavigationBlock
   */
  protected $block;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);

    $this->block = new AnchorLinkNavigationBlock([], 'anchor_link_navigation', ['provider' => 'jumpstart_ui']);
  }

  /**
   * The default configuration only has a null orientation.
   */
  public function testDefaultConfiguration(): void {
    $this->assertSame(['orientation' => NULL], $this->block->defaultConfiguration());
  }

  /**
   * The block form contains an orientation select with the expected options.
   */
  public function testBlockForm(): void {
    $form_state = new FormState();
    $form = $this->block->blockForm([], $form_state);

    $this->assertArrayHasKey('orientation', $form);
    $this->assertEquals('select', $form['orientation']['#type']);
    $this->assertEquals('Link Orientation', (string) $form['orientation']['#title']);
    $this->assertNull($form['orientation']['#default_value']);
    $this->assertEquals('Vertical', (string) $form['orientation']['#empty_option']);
    $this->assertEquals(['horizontal' => 'Horizontal'], array_map('strval', $form['orientation']['#options']));
  }

  /**
   * The block form reflects a previously configured orientation.
   */
  public function testBlockFormWithExistingConfiguration(): void {
    $this->block->setConfiguration(['orientation' => 'horizontal']);
    $form_state = new FormState();
    $form = $this->block->blockForm([], $form_state);

    $this->assertEquals('horizontal', $form['orientation']['#default_value']);
  }

  /**
   * Submitting the block form stores the selected orientation.
   */
  public function testBlockSubmit(): void {
    $form_state = new FormState();
    $form_state->setValue('orientation', 'horizontal');

    $this->block->blockSubmit([], $form_state);

    $this->assertEquals('horizontal', $this->block->getConfiguration()['orientation']);
  }

  /**
   * Submitting with no value stored clears the orientation.
   */
  public function testBlockSubmitWithNoValue(): void {
    $form_state = new FormState();

    $this->block->blockSubmit([], $form_state);

    $this->assertNull($this->block->getConfiguration()['orientation']);
  }

  /**
   * With no orientation configured, the build defaults to vertical.
   */
  public function testBuildDefaultsToVertical(): void {
    $build = $this->block->build();

    $this->assertArrayHasKey('content', $build);
    $this->assertEquals('html_tag', $build['content']['#type']);
    $this->assertEquals('div', $build['content']['#tag']);
    $this->assertEquals('', $build['content']['#value']);
    $this->assertEquals([
      'anchor-link-nav',
      'orientation-vertical',
    ], $build['content']['#attributes']['class']);
    $this->assertEquals(['jumpstart_ui/anchor_link_nav'], $build['content']['#attached']['library']);
  }

  /**
   * With orientation configured as horizontal, the build reflects that.
   */
  public function testBuildWithHorizontalOrientation(): void {
    $this->block->setConfiguration(['orientation' => 'horizontal']);
    $build = $this->block->build();

    $this->assertEquals([
      'anchor-link-nav',
      'orientation-horizontal',
    ], $build['content']['#attributes']['class']);
  }

  /**
   * An empty-string orientation (falsy) also falls back to vertical.
   */
  public function testBuildWithEmptyStringOrientationDefaultsToVertical(): void {
    $this->block->setConfiguration(['orientation' => '']);
    $build = $this->block->build();

    $this->assertEquals([
      'anchor-link-nav',
      'orientation-vertical',
    ], $build['content']['#attributes']['class']);
  }

}
