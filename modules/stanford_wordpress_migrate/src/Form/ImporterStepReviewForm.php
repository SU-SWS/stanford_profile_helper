<?php

namespace Drupal\stanford_wordpress_migrate\Form;

use Drupal\Core\Form\FormStateInterface;

/**
 * Simple wizard step form.
 */
class ImporterStepReviewForm extends WordPressImporterFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity.wordpress_migration.step_6';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form = parent::buildForm($form, $form_state);

    $cached_values = $form_state->getTemporaryValue(['wizard']);
    /** @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration */
    $migration = $cached_values['wordpress_migration'];

    $taxonomy_mappings = $migration->getConfigurationValue('taxonomy_term', []);
    if ($taxonomy_mappings) {
      $form['taxonomy'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Taxonomy Mappings'),
      ];
      $form['taxonomy']['table'] = [
        '#type' => 'table',
        '#header' => [$this->t('Source'), $this->t('Destination')],
      ];
    }
    foreach ($taxonomy_mappings as $source => $destinations) {
      $form['taxonomy']['table'][$source]['source'] = ['#markup' => $source];
      $form['taxonomy']['table'][$source]['destination'] = ['#markup' => implode(', ', array_keys($destinations))];
    }

    $config = $migration->getConfiguration();
    foreach ($config as $entity_type => $content_mappings) {
      $form[$entity_type] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Content Mappings'),
      ];
      $form[$entity_type]['table'] = [
        '#type' => 'table',
        '#header' => [$this->t('Source'), $this->t('Destination')],
      ];

      foreach ($content_mappings as $source => $destinations) {
        foreach ($destinations as $destination => $field_mappings) {
          $row = [
            'source' => ['#markup' => $source],
            'destination' => ['#markup' => $destination],
          ];
          $form[$entity_type]['table'][] = $row;
        }
      }
    }

    return $form;
  }

}
