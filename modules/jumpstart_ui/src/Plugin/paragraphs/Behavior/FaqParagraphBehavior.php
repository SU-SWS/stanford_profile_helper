<?php

namespace Drupal\jumpstart_ui\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;
use Drupal\paragraphs\ParagraphsTypeInterface;

/**
 * Class FaqParagraphBehavior.
 *
 * @ParagraphsBehavior(
 *   id = "faq_accordions",
 *   label = @Translation("FAQ Accordion"),
 *   description = @Translation("Display options for the FAQ Accordion paragraph.")
 * )
 */
class FaqParagraphBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return ['heading' => 'h2'];
  }

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(ParagraphsTypeInterface $paragraphs_type) {
    return $paragraphs_type->id() == 'stanford_faq';
  }

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $form['heading'] = [
      '#type' => 'select',
      '#title' => $this->t('Heading Level'),
      '#options' => [
        'h2' => 'H2',
        'h3' => 'H3',
        'h4' => 'H4',
      ],
      '#default_value' => $paragraph->getBehaviorSetting('faq_accordions', 'heading', 'h2'),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $display, $view_mode) {
    if (isset($build['su_faq_headline'][0]['#tag'])) {
      $build['su_faq_headline'][0]['#tag'] = $paragraph->getBehaviorSetting('faq_accordions', 'heading', 'h2');
    }
  }

}
