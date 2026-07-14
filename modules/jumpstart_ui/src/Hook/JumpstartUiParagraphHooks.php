<?php

declare(strict_types=1);

namespace Drupal\jumpstart_ui\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Template\Attribute;

class JumpstartUiParagraphHooks {

  #[Hook('preprocess_paragraph__stanford_banner')]
  public function preprocessParagraphStanfordBanner(&$variables) {
    $headline_attributes = new Attribute();

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
        //        'super_headline' =>
        'headline' => $variables['content']['su_banner_header'] ?? NULL,
        'body' => [
          $variables['content']['su_banner_sup_header'] ?? NULL,
          $variables['content']['su_banner_body'] ?? NULL,
        ],
        'button_link' => isset($variables['content']['su_banner_button'][0]['#url']) ? $variables['content']['su_banner_button'][0]['#url']->toString() : NULL,
        'button_label' => $variables['content']['su_banner_button'][0]['#title'] ?? NULL,
      ],
    ];
    /**
     * {{ include('jumpstart_ui:hero', {
     * image: elements.su_banner_image,
     * super_headline: elements.su_banner_sup_header,
     * headline: elements.su_banner_header,
     * body: elements.su_banner_body,
     * cta_link: elements.su_banner_button.0["#url_title"],
     * cta_label: elements.su_banner_button.0["#title"],
     * button_link: elements.su_banner_button.0["#url_title"],
     * button_label: elements.su_banner_button.0["#title"]
     * }, with_context = false) }}
     */
  }

}
