<?php

namespace Drupal\stanford_layout_paragraphs\Layouts;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * One column layout class.
 */
trait LayoutWithBgColorTrait {

  /**
   * Add the background color picker element to the layout form.
   *
   * @param array $form
   *   Layout configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   The altered form.
   */
  protected function addBackgroundColorElement(array &$form, FormStateInterface $form_state) {
    $id = Html::getUniqueId('color-field-' . $this->getPluginId());
    $default_color = $this->configuration['bg_color'] ?? '';
    $form['bg_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background Color'),
      '#default_value' => $default_color ? '#' . $default_color : '',
      '#suffix' => "<div class='color-field-widget-box-form' id='$id'></div>",
      '#maxlength' => 7,
      '#size' => 7,
      '#attached' => [
        'library' => ['color_field/color-field-widget-box'],
        'drupalSettings' => [
          'color_field' => [
            'color_field_widget_box' => [
              'settings' => [
                $id => [
                  'required' => FALSE,
                  'palette' => [
                    '#f4f4f4',
                    '#ebeae5',
                    '#dcecef',
                    '#dcefec',
                    '#f2e8f1',
                    '#f7ecde',
                  ],
                ],
              ],
            ],
          ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Add the section padding and margin select elements to the layout form.
   *
   * @param array $form
   *   Layout configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   The altered form.
   */
  protected function addPaddingMarginElements(array &$form, FormStateInterface $form_state) {
    $form['top_padding'] = [
      '#type' => 'select',
      '#title' => $this->t('Space inside the section - Top'),
      '#description' => $this->t('This would be equivalent to "padding-top".'),
      '#default_value' => $this->configuration['top_padding'] ?? NULL,
      '#empty_option' => $this->t('Default'),
      '#options' => [
        'none' => $this->t('None'),
        'more' => $this->t('Add More'),
      ],
    ];
    $form['bottom_padding'] = [
      '#type' => 'select',
      '#title' => $this->t('Space inside the section - Bottom'),
      '#description' => $this->t('This would be equivalent to "padding-bottom".'),
      '#default_value' => $this->configuration['bottom_padding'] ?? NULL,
      '#empty_option' => $this->t('Default'),
      '#options' => [
        'none' => $this->t('None'),
      ],
    ];
    $form['bottom_margin'] = [
      '#type' => 'select',
      '#title' => $this->t('Space below section'),
      '#description' => $this->t('This would be equivalent to "margin-bottom".'),
      '#default_value' => $this->configuration['bottom_margin'] ?? NULL,
      '#empty_option' => $this->t('Default'),
      '#options' => [
        'none' => $this->t('None'),
      ],
    ];
    return $form;
  }

  /**
   * Save the submitted background color, without the "#", to configuration.
   *
   * @param array $form
   *   Layout configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function submitBackgroundForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['bg_color'] = strtolower(str_replace('#', '', $form_state->getValue('bg_color')));
  }

  /**
   * Save the submitted padding and margin values to configuration.
   *
   * @param array $form
   *   Layout configuration form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  protected function submitPaddingMarginForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['top_padding'] = $form_state->getValue('top_padding');
    $this->configuration['bottom_padding'] = $form_state->getValue('bottom_padding');
    $this->configuration['bottom_margin'] = $form_state->getValue('bottom_margin');
  }

}
