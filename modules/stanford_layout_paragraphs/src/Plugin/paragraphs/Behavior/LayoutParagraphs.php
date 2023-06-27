<?php

namespace Drupal\stanford_layout_paragraphs\Plugin\paragraphs\Behavior;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_paragraphs\Plugin\paragraphs\Behavior\LayoutParagraphsBehavior;
use Drupal\paragraphs\ParagraphInterface;

/**
 * Modifies and adds to the LP behavior plugin.
 */
class LayoutParagraphs extends LayoutParagraphsBehavior {

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $form['layout']['#attributes']['class'][] = 'choose-layout-field';
    if (isset($form['config'])) {
      $form['config']['#open'] = TRUE;
    }
    return $form;
  }

}
