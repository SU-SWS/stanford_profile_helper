<?php

declare(strict_types=1);

namespace Drupal\jumpstart_ui\Hook;

use Drupal\Core\Hook\Attribute\Hook;

class JumpstartUiParagraphHooks {

  #[Hook('preprocess_paragraph__stanford_banner')]
  public function preprocessParagraphStanfordBanner(&$variables) {
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $variables['paragraph'];

    $header_behavior = $paragraph->getBehaviorSetting('hero_pattern', 'heading', 'h2');
    preg_match('/^(\w+)(.*)$/', $header_behavior, $header_tag);

    $variables['content'] = [
      '#type' => 'component',
      '#component' => 'jumpstart_ui:hero',
      '#props' => [
        'header_tag' => $header_tag[1],
        'header_classes' => isset($header_tag[2]) ? trim(str_replace('.', ' ', $header_tag[2])) : NULL,
        'visually_hide_header' => (bool) $paragraph->getBehaviorSetting('hero_pattern', 'hide_heading', FALSE),
        'overlay_position' => $paragraph->getBehaviorSetting('hero_pattern', 'overlay_position', 'left'),
        'wrapper_class' => 'bottom-margin-' . $paragraph->getBehaviorSetting('hero_pattern', 'space_below', 'default'),
      ],
      '#slots' => [
        'image' => $variables['content']['su_banner_image'] ?? NULL,
        'headline' => $variables['content']['su_banner_header'] ?? NULL,
        'body' => [
          $variables['content']['su_banner_sup_header'] ?? NULL,
          $variables['content']['su_banner_body'] ?? NULL,
        ],
        'button_link' => isset($variables['content']['su_banner_button'][0]['#url']) ? $variables['content']['su_banner_button'][0]['#url']->toString() : NULL,
        'button_label' => $variables['content']['su_banner_button'][0]['#title'] ?? NULL,
      ],
    ];
  }

  #[Hook('preprocess_paragraph__stanford_card')]
  public function preprocessParagraphStanfordCard(&$variables) {
    /** @var \Drupal\paragraphs\ParagraphInterface $paragraph */
    $paragraph = $variables['paragraph'];

    $header_behavior = $paragraph->getBehaviorSetting('su_card_styles', 'heading', 'h2');
    preg_match('/^(\w+)(.*)$/', $header_behavior, $header_tag);
    $link_style = $paragraph->getBehaviorSetting('su_card_styles', 'link_style', 'button');
    $variables['content'] = [
      '#type' => 'component',
      '#component' => 'jumpstart_ui:card',
      '#props' => [
        'header_tag' => $header_tag[1],
        'header_classes' => isset($header_tag[2]) ? trim(str_replace('.', ' ', $header_tag[2])) : NULL,
        'visually_hide_header' => (bool) $paragraph->getBehaviorSetting('su_card_styles', 'hide_heading', FALSE),
      ],
      '#slots' => [
        'image' => $variables['content']['su_card_media'] ?? NULL,
        'headline' => $variables['content']['su_card_header'] ?? NULL,
        'body' => [
          $variables['content']['su_card_super_header'] ?? NULL,
          $variables['content']['su_card_body'] ?? NULL,
        ],
        'link' => isset($variables['content']['su_card_link'][0]['#url']) ? $variables['content']['su_card_link'][0]['#url']->toString() : NULL,
        'button_label' => $link_style != 'action' ? $variables['content']['su_card_link'][0]['#title'] ?? NULL : NULL,
        'cta_label' => $link_style == 'action' ? $variables['content']['su_card_link'][0]['#title'] ?? NULL : NULL,
      ],
    ];
  }

  #[Hook('preprocess_paragraph__stanford_media_caption')]
  public function preprocessParagraphStanfordMediaCaption(&$variables) {
    $variables['content'] = [
      '#type' => 'component',
      '#component' => 'jumpstart_ui:media',
      '#slots' => [
        'image' => $variables['content']['su_media_caption_media'] ?? NULL,
        'caption' => $variables['content']['su_media_caption_caption'] ?? NULL,
        'link' => $variables['content']['su_media_caption_link']?? null,
      ],
    ];
  }

  #[Hook('preprocess_paragraph__stanford_accordion')]
  public function preprocessParagraphStanfordAccordion(&$variables) {
    $variables['content'] = [
      '#type' => 'component',
      '#component' => 'jumpstart_ui:accordion',
      '#slots' => [
        'title' => $variables['content']['su_accordion_title'] ?? NULL,
        'contents' => $variables['content']['su_accordion_body'] ?? NULL,
      ],
    ];
  }

}
