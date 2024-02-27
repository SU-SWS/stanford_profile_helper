<?php

namespace Drupal\stanford_paragraph_card\Plugin\paragraphs\Behavior;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\paragraphs\ParagraphsBehaviorBase;

/**
 * Teaser paragraph behaviors.
 *
 * @ParagraphsBehavior(
 *   id = "su_card_styles",
 *   label = @Translation("Card Styles"),
 *   description = @Translation("Style options for card paragraph")
 * )
 */
class CardBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(ParagraphsType $paragraphs_type): bool {
    return $paragraphs_type->id() == 'stanford_card';
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    return [
      'heading' => 'h2',
      'hide_heading' => FALSE,
      'link_style' => NULL,
    ];
  }

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state): array {
    $element = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $element['heading'] = [
      '#type' => 'select',
      '#title' => $this->t('Heading Level'),
      '#options' => [
        'h2' => 'H2',
        'h3' => 'H3',
        'h4' => 'H4',
        'div.su-font-splash' => $this->t('Splash Text'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('su_card_styles', 'heading', 'h2'),
    ];
    $element['hide_heading'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Visually Hide Heading'),
      '#default_value' => $paragraph->getBehaviorSetting('su_card_styles', 'hide_heading', FALSE),
    ];
    $element['link_style'] = [
      '#title' => $this->t('Link Style'),
      '#description' => $this->t('Choose how you would like the link to display.'),
      '#type' => 'select',
      '#empty_option' => $this->t('Button'),
      '#options' => [
        'action' => $this->t('Action Link'),
      ],
      '#default_value' => $paragraph->getBehaviorSetting('su_card_styles', 'link_style'),
    ];
    return $element;
  }

  /**
   * {@inheritDoc}
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $display, $view_mode): void {
    $link_style = $paragraph->getBehaviorSetting('su_card_styles', 'link_style');
    if ($link_style == 'action') {
      // Change the DS config going in to the render.
      $build['#ds_configuration']['regions']['card_cta_label'] = $build['#ds_configuration']['regions']['card_button_label'];
      unset($build['#ds_configuration']['regions']['card_button_label']);
    }
  }

}
