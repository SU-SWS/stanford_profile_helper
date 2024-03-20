<?php

namespace Drupal\jumpstart_ui\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\paragraphs\ParagraphsTypeInterface;

/**
 * Class HeroPatternBehavior.
 *
 * @ParagraphsBehavior(
 *   id = "stanford_teaser",
 *   label = @Translation("Teaser Paragraph"),
 *   description = @Translation("Display options for the Teaser paragraph.")
 * )
 */
class TeaserParagraphBehavior extends ParagraphsBehaviorBase {

  const SHOW_HEADING = 'show';

  const HIDE_HEADING = 'hide';

  const REMOVE_HEADING = 'remove';

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
    return ['heading_behavior' => 'show'];
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
        self::HIDE_HEADING => $this->t('<strong>Visually hide heading</strong>: This keeps the headline in the page structure as an H2, but you won’t see it.'),
        self::REMOVE_HEADING => $this->t('<strong>Remove heading</strong>: This completely removes the headline from the page and assumes you have placed an H2 on the page above this paragraph.<em>Note: Used incorrectly, removing the heading can create an accessibility issue.</em>'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('stanford_teaser', 'heading_behavior', self::SHOW_HEADING),
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
  }

}
