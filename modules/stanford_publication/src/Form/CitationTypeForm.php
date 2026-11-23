<?php

namespace Drupal\stanford_publication\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;

/**
 * Add and edit form for citation type config entities.
 */
class CitationTypeForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);

    $citation_type = $this->entity;
    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Label'),
      '#maxlength' => 255,
      '#default_value' => $citation_type->label(),
      '#description' => $this->t("Label for the Citation type."),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $citation_type->id(),
      '#machine_name' => [
        'exists' => '\Drupal\stanford_publication\Entity\CitationType::load',
      ],
      '#disabled' => !$citation_type->isNew(),
    ];

    // CSL type identifiers are used as their own labels and not translated.
    $csl_types = [
      'article',
      'article-magazine',
      'article-newspaper',
      'article-journal',
      'bill',
      'book',
      'broadcast',
      'chapter',
      'dataset',
      'entry',
      'entry-dictionary',
      'entry-encyclopedia',
      'figure',
      'graphic',
      'interview',
      'legislation',
      'legal_case',
      'manuscript',
      'map',
      'motion_picture',
      'musical_score',
      'other',
      'pamphlet',
      'paper-conference',
      'patent',
      'post',
      'post-weblog',
      'personal_communication',
      'report',
      'review',
      'review-book',
      'song',
      'speech',
      'thesis',
      'treaty',
      'webpage',
    ];

    $link = Link::fromTextAndUrl('CSL documentation', Url::fromUri('https://docs.citationstyles.org/en/1.0.1/specification.html#appendix-iii-types'));
    $form['type'] = [
      '#type' => 'select',
      '#title' => $this->t('Citation Type'),
      '#description' => $this->t('Based on the CSL type declaration. See %link', ['%link' => $link->toString()]),
      '#options' => array_combine($csl_types, $csl_types),
      '#default_value' => $citation_type->type(),
      '#required' => TRUE,
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $citation_type = $this->entity;
    $status = $citation_type->save();

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()
          ->addMessage($this->t('Created the %label Citation type.', [
            '%label' => $citation_type->label(),
          ]));
        break;

      default:
        $this->messenger()
          ->addMessage($this->t('Saved the %label Citation type.', [
            '%label' => $citation_type->label(),
          ]));
    }
    $form_state->setRedirectUrl($citation_type->toUrl('collection'));
    return $status;
  }

}
