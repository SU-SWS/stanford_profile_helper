<?php

namespace Drupal\stanford_layout_paragraphs\Layouts;

use Drupal\Core\Form\FormStateInterface;
use Drupal\layout_builder\Plugin\Layout\MultiWidthLayoutBase;

/**
 * Two column layout class.
 */
class TwoColumn extends MultiWidthLayoutBase {

  use LayoutWithBgColorTrait;

  /**
   * {@inheritDoc}
   *
   * @codeCoverageIgnore Nothing to test.
   */
  protected function getWidthOptions() {
    return [
      '50-50' => 'Equal Columns',
      '33-67' => 'Larger Right Column',
      '67-33' => 'Larger Left Column',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['bg_color'] = NULL;
    $configuration['top_padding'] = NULL;
    $configuration['bottom_padding'] = NULL;
    $configuration['bottom_margin'] = NULL;
    $configuration['vertical_dividers'] = NULL;
    return $configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form = $this->addBackgroundColorElement($form, $form_state);
    $form = $this->addPaddingMarginElements($form, $form_state);
    $form['vertical_dividers'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Add vertical dividers'),
      '#default_value' => $this->configuration['vertical_dividers'] ?? FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->submitBackgroundForm($form, $form_state);
    $this->submitPaddingMarginForm($form, $form_state);
    $this->configuration['vertical_dividers'] = (bool) $form_state->getValue('vertical_dividers');
  }

}
