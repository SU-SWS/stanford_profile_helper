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
    return ['hide_heading' => FALSE];
  }

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $form['hide_heading'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Visually Hide Heading'),
      '#default_value' => $paragraph->getBehaviorSetting('stanford_teaser', 'hide_heading', FALSE),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    if (!isset($build['su_entity_headline'][0]) || !isset($build['su_entity_item'][0])) {
      return;
    }
    if ($paragraph->getBehaviorSetting('stanford_teaser', 'hide_heading', FALSE)) {
      $build['su_entity_headline']['#attributes']['class'][] = 'visually-hidden';
    }
    foreach (Element::children($build['su_entity_item']) as $delta) {
      $build['su_entity_item'][$delta]['#view_mode'] = 'stanford_h3_card';
    }
  }

}
