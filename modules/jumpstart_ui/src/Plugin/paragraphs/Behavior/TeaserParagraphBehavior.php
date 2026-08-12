<?php

namespace Drupal\jumpstart_ui\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\paragraphs\Attribute\ParagraphsBehavior;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\paragraphs\ParagraphsTypeInterface;

/**
 * Class HeroPatternBehavior.
 */
#[ParagraphsBehavior(
  id: 'stanford_teaser',
  label: new TranslatableMarkup('Teaser Paragraph'),
  description: new TranslatableMarkup('Display options for the Teaser paragraph.')
)]
class TeaserParagraphBehavior extends ParagraphsBehaviorBase {

  const SHOW_HEADING = 'show';

  const HIDE_HEADING = 'hide';

  const REMOVE_HEADING = 'remove';

  const LARGE_IMAGE = 'large';

  const SMALL_IMAGE = 'small';

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(ParagraphsTypeInterface $paragraphs_type) {
    return $paragraphs_type->id() == 'stanford_entity';
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'heading_behavior' => 'show',
      'image_size' => self::LARGE_IMAGE,
    ];
  }

  /**
   * Check if the paragraph contains a spotlight news item.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   The paragraph entity.
   *
   * @return bool
   *   TRUE if the paragraph contains a spotlight news item.
   */
  protected function hasSpotlightNews(ParagraphInterface $paragraph) {
    if (!$paragraph->hasField('su_entity_item')) {
      return FALSE;
    }

    $entity_items = $paragraph->get('su_entity_item')->referencedEntities();
    foreach ($entity_items as $entity) {
      // Check if the entity is a stanford_news node.
      if ($entity->getEntityTypeId() === 'node' && $entity->bundle() === 'stanford_news') {
        // Check if it has the spotlight layout.
        if ($entity->hasField('layout_selection') && !$entity->get('layout_selection')->isEmpty()) {
          $layout = $entity->get('layout_selection')->entity;
          if ($layout && $layout->id() === 'news_spotlight') {
            return TRUE;
          }
        }
      }
    }

    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $form['heading_behavior'] = [
      '#type' => 'radios',
      '#title' => $this->t('Headline Behavior'),
      '#options' => [
        self::SHOW_HEADING => $this->t('<strong>Display heading</strong>: Recommended - This displays the paragraph headline as an H2.'),
        self::HIDE_HEADING => $this->t('<strong>Visually hide heading</strong>: This keeps the headline in the page structure as an H2, but you won\'t see it.'),
        self::REMOVE_HEADING => $this->t('<strong>Remove heading</strong>: This completely removes the headline from the page and assumes you have placed an H2 on the page above this paragraph.<em>Note: Used incorrectly, removing the heading can create an accessibility issue.</em>'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('stanford_teaser', 'heading_behavior', self::SHOW_HEADING),
    ];

    // Always show the image size option with a helpful description.
    $form['image_size'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Size'),
      '#description' => $this->t('<strong>Note:</strong> This setting only applies to News items that have the <em>Spotlight</em> layout selected. It will be ignored for other content types or layout options.'),
      '#options' => [
        self::LARGE_IMAGE => $this->t('Large Image'),
        self::SMALL_IMAGE => $this->t('Small Image'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('stanford_teaser', 'image_size', self::LARGE_IMAGE),
    ];

    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    $heading_behavior = $paragraph->getBehaviorSetting('stanford_teaser', 'heading_behavior', self::SHOW_HEADING);

    // Heading is populated or configured to be removed, change the display mode
    // of the entities.
    if (
      isset($build['su_entity_item']) &&
      (isset($build['su_entity_headline'][0]) || $heading_behavior == self::REMOVE_HEADING)
    ) {
      foreach (Element::children($build['su_entity_item']) as $delta) {
        $build['su_entity_item'][$delta]['#view_mode'] = 'stanford_h3_card';

        // Replace the cache keys to match the view mode.
        $cache_key = array_search('stanford_card', $build['su_entity_item'][$delta]['#cache']['keys']);
        $build['su_entity_item'][$delta]['#cache']['keys'][$cache_key] = 'stanford_h3_card';
      }
    }

    if ($heading_behavior == self::HIDE_HEADING) {
      $build['su_entity_headline']['#attributes']['class'][] = 'visually-hidden';
    }

    if ($heading_behavior == self::REMOVE_HEADING) {
      unset($build['su_entity_headline']);
    }

    // If this is a spotlight news teaser, add the image size setting.
    if ($this->hasSpotlightNews($paragraph)) {
      $image_size = $paragraph->getBehaviorSetting('stanford_teaser', 'image_size', self::LARGE_IMAGE);
      // Add the image size as a variable to the build array.
      $build['#spotlight_image_size'] = $image_size;
      // Also add it as a CSS class for easier styling.
      $build['#attributes']['class'][] = 'spotlight-image-' . $image_size;
    }
  }

}
