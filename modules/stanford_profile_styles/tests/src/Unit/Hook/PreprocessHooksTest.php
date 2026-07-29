<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_styles\Unit\Hook;

use Drupal\config_pages\ConfigPagesInterface;
use Drupal\Core\Template\Attribute;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_profile_styles\Hook\PreprocessHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for PreprocessHooks.
 */
#[Group('stanford_profile_styles')]
#[CoversClass(PreprocessHooks::class)]
class PreprocessHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_styles\Hook\PreprocessHooks
   */
  protected PreprocessHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new PreprocessHooks();
  }

  /**
   * A wysiwyg-ish field type gets the su-wysiwyg-text class and library.
   */
  public function testPreprocessFieldWysiwygType(): void {
    $variables = ['field_type' => 'text_with_summary'];
    $this->hooks->preprocessField($variables);

    $this->assertContains('su-wysiwyg-text', $variables['attributes']['class']);
    $this->assertContains('stanford_profile_styles/paragraph.wysiwyg', $variables['#attached']['library']);
  }

  /**
   * A non wysiwyg field type is left untouched.
   */
  public function testPreprocessFieldOtherType(): void {
    $variables = ['field_type' => 'string'];
    $this->hooks->preprocessField($variables);

    $this->assertArrayNotHasKey('attributes', $variables);
    $this->assertArrayNotHasKey('#attached', $variables);
  }

  /**
   * The react paragraph row preprocess adds container classes and flexes
   * each item according to its react width behavior setting, for array
   * style attributes.
   */
  public function testPreprocessFieldReactParagraphRowArrayAttributes(): void {
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')
      ->with('react', 'width', 12)
      ->willReturn(6);

    $variables = [
      'items' => [
        [
          'content' => ['#paragraph' => $paragraph],
          'attributes' => [],
        ],
      ],
    ];
    $this->hooks->preprocessFieldReactParagraphRow($variables);

    $this->assertContains('container-1-items', $variables['attributes']['class']);
    $this->assertContains('flex-container', $variables['attributes']['class']);
    $this->assertSame([1], $variables['attributes']['data-item-count']);
    $this->assertContains('flex-6-of-12', $variables['items'][0]['attributes']['class']);
  }

  /**
   * When width resolves to 0, no flex class is added — the falsy check
   * short-circuits the inner if.
   */
  public function testPreprocessFieldReactParagraphRowZeroWidth(): void {
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')->willReturn(0);

    $variables = [
      'items' => [
        [
          'content' => ['#paragraph' => $paragraph],
          'attributes' => [],
        ],
      ],
    ];
    $this->hooks->preprocessFieldReactParagraphRow($variables);

    $this->assertArrayNotHasKey('class', $variables['items'][0]['attributes']);
  }

  /**
   * When the item attributes are an Attribute object, addClass() is used
   * instead of manipulating an array key.
   */
  public function testPreprocessFieldReactParagraphRowAttributeObject(): void {
    $paragraph = $this->createMock(ParagraphInterface::class);
    $paragraph->method('getBehaviorSetting')
      ->with('react', 'width', 12)
      ->willReturn(4);

    $variables = [
      'items' => [
        [
          'content' => ['#paragraph' => $paragraph],
          'attributes' => new Attribute(),
        ],
      ],
    ];
    $this->hooks->preprocessFieldReactParagraphRow($variables);

    $this->assertStringContainsString('flex-4-of-12', (string) $variables['items'][0]['attributes']);
  }

  /**
   * Multiple items produce the correct item count in classes and data
   * attribute.
   */
  public function testPreprocessFieldReactParagraphRowMultipleItems(): void {
    $paragraph_a = $this->createMock(ParagraphInterface::class);
    $paragraph_a->method('getBehaviorSetting')->willReturn(6);
    $paragraph_b = $this->createMock(ParagraphInterface::class);
    $paragraph_b->method('getBehaviorSetting')->willReturn(6);

    $variables = [
      'items' => [
        ['content' => ['#paragraph' => $paragraph_a], 'attributes' => []],
        ['content' => ['#paragraph' => $paragraph_b], 'attributes' => []],
      ],
    ];
    $this->hooks->preprocessFieldReactParagraphRow($variables);

    $this->assertContains('container-2-items', $variables['attributes']['class']);
    $this->assertSame([2], $variables['attributes']['data-item-count']);
  }

  /**
   * The super footer config page block gets the special block class and
   * library.
   */
  public function testPreprocessBlockConfigPagesBlockSuperFooter(): void {
    $config_page = $this->createMock(ConfigPagesInterface::class);
    $config_page->method('bundle')->willReturn('stanford_super_footer');

    $variables = [
      'content' => ['#config_pages' => $config_page],
    ];
    $this->hooks->preprocessBlockConfigPagesBlock($variables);

    $this->assertContains('block-config-pages-super-footer', $variables['attributes']['class']);
    $this->assertContains('stanford_profile_styles/blocks.config_pages.super-footer', $variables['#attached']['library']);
  }

  /**
   * A different config page bundle gets no special treatment.
   */
  public function testPreprocessBlockConfigPagesBlockOtherBundle(): void {
    $config_page = $this->createMock(ConfigPagesInterface::class);
    $config_page->method('bundle')->willReturn('stanford_basic_site_settings');

    $variables = [
      'content' => ['#config_pages' => $config_page],
    ];
    $this->hooks->preprocessBlockConfigPagesBlock($variables);

    $this->assertArrayNotHasKey('attributes', $variables);
    $this->assertArrayNotHasKey('#attached', $variables);
  }

  /**
   * The colorbox formatter preprocess always attaches its library.
   */
  public function testPreprocessColorboxFormatter(): void {
    $variables = [];
    $this->hooks->preprocessColorboxFormatter($variables);

    $this->assertContains('stanford_profile_styles/colorbox', $variables['#attached']['library']);
  }

}
