<?php

namespace Drupal\stanford_layout_paragraphs\Layouts;

use Drupal\Component\Utility\Html;
use Drupal\Core\Form\FormStateInterface;

/**
 * One column layout class.
 */
trait LayoutWithBgColorTrait {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $id = Html::getUniqueId('color-field-' . $this->getPluginId());
    $form['bg_color'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Background Color'),
      '#default_value' => '#' . $this->configuration['bg_color'],
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
                    '#dad7cb',
                    '#c5e0e5',
                    '#d2eae6',
                    '#f0e5ef',
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
   * {@inheritdoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::submitConfigurationForm($form, $form_state);
    $this->configuration['bg_color'] = strtolower(str_replace('#', '', $form_state->getValue('bg_color')));
  }

}
