<?php

namespace Drupal\stanford_layout_paragraphs\Layouts;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Layout\LayoutDefault;

/**
 * One column layout class.
 */
class OneColumn extends LayoutDefault {

  use LayoutWithBgColorTrait;

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $from = $this->addBackgroundColorElement($form, $form_state);
    $form = $this->addPaddingMarginElements($form, $form_state);
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->submitBackgroundForm($form, $form_state);
    $this->submitPaddingMarginForm($form, $form_state);
  }

}
