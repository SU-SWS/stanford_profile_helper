<?php

declare(strict_types=1);

namespace Drupal\stanford_publication\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hooks that alter the taxonomy term overview form for publication topics.
 */
class FormHooks {

  use StringTranslationTrait;

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   */
  public function __construct(protected StateInterface $state) {}

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_taxonomy_overview_terms_alter')]
  public function formTaxonomyOverviewTermsAlter(&$form, FormStateInterface $form_state): void {
    if ($form_state->get('taxonomy')['vocabulary']->id() == 'stanford_publication_topics') {
      $form['citation_format'] = [
        '#type' => 'select',
        '#title' => $this->t('Citation Format'),
        '#description' => $this->t('Change the citation format for the publication items displayed on the taxonomy pages.'),
        '#options' => [
          'apa' => 'APA',
          'chicago' => $this->t('Chicago'),
        ],
        '#default_value' => $this->state
          ->get('stanford_publication.citation_format', 'chicago'),
      ];
      $form['#submit'][] = [self::class, 'termOverviewSubmit'];
    }
  }

  /**
   * Taxonomy term overview form submit to save the citation format value.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   */
  public static function termOverviewSubmit(array $form, FormStateInterface $form_state): void {
    $state = \Drupal::state();
    if ($state->get('stanford_publication.citation_format') != $form_state->getValue('citation_format')) {
      $state->set('stanford_publication.citation_format', $form_state->getValue('citation_format'));
      Cache::invalidateTags(['citation_view']);
    }
  }

}
