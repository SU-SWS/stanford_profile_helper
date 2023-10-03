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
 *   id = "list_paragraph",
 *   label = @Translation("List Paragraph"),
 *   description = @Translation("Alter the display of the list paragraph.")
 * )
 */
class ListParagraphBehavior extends ParagraphsBehaviorBase {

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(ParagraphsTypeInterface $paragraphs_type) {
    return $paragraphs_type->id() == 'stanford_lists';
  }

  /**
   * {@inheritDoc}
   */
  public function buildBehaviorForm(ParagraphInterface $paragraph, array &$form, FormStateInterface $form_state) {
    $form = parent::buildBehaviorForm($paragraph, $form, $form_state);
    $form['hide_empty'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Hide if empty'),
      '#description' => $this->t('Disable paragraph if there are no items displayed in the list.<br>Disabled content will not be accessed by site visitors. This applies only if there are no items within the list.'),
      '#default_value' => $paragraph->getBehaviorSetting('list_paragraph', 'hide_empty', FALSE),
    ];
    $form['empty_message'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Empty list message.'),
      '#description' => $this->t('This message will appear for site visitors if there are no items displayed in the list.'),
      '#default_value' => $paragraph->getBehaviorSetting('list_paragraph', 'empty_message'),
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function view(array &$build, ParagraphInterface $paragraph, EntityViewDisplayInterface $display, $view_mode) {

    if (!isset($build['su_list_view']) || !empty(Element::children($build['su_list_view']))) {
      return;
    }

    // If there is no empty message set, it will be null.
    $build['su_list_view']['#markup'] = $paragraph->getBehaviorSetting('list_paragraph', 'empty_message');

    if ($paragraph->getBehaviorSetting('list_paragraph', 'hide_empty')) {
      // D8CORE-6715: If the list is empty, the paragraph becomes uneditable.
      unset($build['su_list_button'], $build['su_list_description'], $build['su_list_headline'], $build['su_list_view']);
    }
  }

}
