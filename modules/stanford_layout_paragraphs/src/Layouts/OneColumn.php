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
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $this->addBackgroundColorElement($form, $form_state);
    $this->addPaddingMarginElements($form, $form_state);
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->submitBackgroundForm($form, $form_state);
    $this->submitPaddingMarginForm($form, $form_state);
  }

}
