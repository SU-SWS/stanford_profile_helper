<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_styles\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Template\Attribute;

/**
 * Preprocess hooks.
 */
class PreprocessHooks {

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_field')]
  public function preprocessField(&$variables): void {
    $wysiwyg_fields = ['text', 'text_with_summary', 'text_long'];
    if (in_array($variables['field_type'], $wysiwyg_fields)) {
      $variables['attributes']['class'][] = 'su-wysiwyg-text';
      $variables['#attached']['library'][] = 'stanford_profile_styles/paragraph.wysiwyg';
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_field__react_paragraph_row')]
  public function preprocessFieldReactParagraphRow(&$variables): void {
    $count = count($variables['items']);
    $variables['attributes']['class'][] = "container-$count-items";
    $variables['attributes']['class'][] = 'flex-container';
    $variables['attributes']['data-item-count'][] = $count;

    foreach ($variables['items'] as &$item) {
      /** @var \Drupal\paragraphs\ParagraphInterface $entity */
      $entity = $item['content']['#paragraph'];

      if ($width = $entity->getBehaviorSetting('react', 'width', 12)) {
        if ($item['attributes'] instanceof Attribute) {
          $item['attributes']->addClass("flex-$width-of-12");
          continue;
        }
        $item['attributes']['class'][] = "flex-$width-of-12";
      }
    }
  }

  /**
   * Implements hook_preprocess_block__config_pages_block().
   */
  #[Hook('preprocess_block__config_pages_block')]
  public function preprocessBlockConfigPagesBlock(&$variables): void {
    // Alter the styles and values for the config form blocks.
    $config_page = $variables['content']['#config_pages'];
    $type = $config_page->bundle();

    // SUPER! Footer!
    if ($type == "stanford_super_footer") {
      $variables['attributes']['class'][] = 'block-config-pages-super-footer';
      $variables['#attached']['library'][] = 'stanford_profile_styles/blocks.config_pages.super-footer';
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_colorbox_formatter')]
  public function preprocessColorboxFormatter(&$variables): void {
    $variables['#attached']['library'][] = 'stanford_profile_styles/colorbox';
  }

}
