<?php

namespace Drupal\stanford_wordpress_migrate\Wizard;

use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\ctools\Wizard\EntityFormWizardBase;
use Drupal\stanford_wordpress_migrate\Form\ImporterStep1SourceSelectForm;
use Drupal\stanford_wordpress_migrate\Form\ImporterStep2EntitySelectForm;
use Drupal\stanford_wordpress_migrate\Form\ImporterStep5FieldMappingForm;
use Drupal\stanford_wordpress_migrate\Form\ImporterStepReviewForm;

use function Symfony\Component\String\u;

/**
 * Form wizard to step through the WordPress migration setup.
 */
class ImportAddWizard extends EntityFormWizardBase {

  /**
   * {@inheritdoc}
   */
  public function getMachineLabel() {
    return $this->t('Site Name');
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityType() {
    return 'wordpress_migration';
  }

  /**
   * {@inheritdoc}
   */
  public function getWizardLabel() {
    return $this->t('WordPress Importer');
  }

  /**
   * {@inheritdoc}
   */
  public function exists() {
    return '\Drupal\stanford_wordpress_migrate\Entity\WordPressMigration::load';
  }

  /**
   * {@inheritdoc}
   */
  public function getRouteName(): string {
    return 'entity.wordpress_migration.add_step_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function customizeForm(array $form, FormStateInterface $form_state) {
    $form = parent::customizeForm($form, $form_state);
    unset($form['name']);
    $form['id'] = ['#type' => 'hidden', '#value' => NULL];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getTemporaryValue([
      'wizard',
      'wordpress_migration',
    ])->isNew()) {
      $this->machine_name = 'new';
    }
    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations($cached_values): array {
    $entityTypes = [
      'taxonomy_term' => $this->entityTypeManager->getDefinition('taxonomy_term'),
      'media' => $this->entityTypeManager->getDefinition('media'),
      'node' => $this->entityTypeManager->getDefinition('node'),
    ];

    $steps = [
      'source' => [
        'form' => ImporterStep1SourceSelectForm::class,
        'title' => $this->t('Data source'),
      ],
    ];
    foreach ($entityTypes as $entityType) {
      $steps[$entityType->id()] = [
        'form' => ImporterStep2EntitySelectForm::class,
        'title' => $entityType->getLabel(),
        'values' => ['entity_type' => $entityType->id()],
      ];
    }

    /** @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration */
    $migration = $cached_values['wordpress_migration'];

    $extra_steps = [];
    foreach ($entityTypes as $entityType) {
      $extra_steps[$entityType->id()] = $migration->getConfigurationValue($entityType->id(), []);
    }

    foreach ($extra_steps as $entity_type_id => $content_mapping) {
      $bundle_entity_type = $this->entityTypeManager->getDefinition($entity_type_id)
        ->getBundleEntityType() ?: $entity_type_id;

      // For each source to destination, add another step for the field mapping.
      foreach ($content_mapping as $source => $destinationMapping) {
        foreach (array_keys($destinationMapping) as $destination) {
          $destination_label = $this->entityTypeManager->getStorage($bundle_entity_type)
            ->load($destination)
            ->label();

          $key = $entity_type_id . '--' . basename($source) . '--';
          $key .= u($destination)
            ->kebab()
            ->toString();

          $steps[$key] = [
            'form' => ImporterStep5FieldMappingForm::class,
            'title' => $this->t('@source to @dest', [
              '@source' => ucwords(str_replace('_', ' ', basename($source))),
              '@dest' => $destination_label,
            ]),
            'values' => [
              'entity_type' => $entity_type_id,
              'source' => $source,
              'destination' => $destination,
            ],
          ];
        }
      }
    }

    $steps['review'] = [
      'form' => ImporterStepReviewForm::class,
      'title' => $this->t('Review'),
      'values' => ['wordpress_content_type' => ''],
    ];
    return $steps;
  }

  protected function actions(FormInterface $form_object, FormStateInterface $form_state) {
    $actions = parent::actions($form_object, $form_state);
    $actions['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#submit' => ['::cancel'],
      '#limit_validation_errors' => [],
    ];
    return $actions;
  }

  /**
   * Form submission handler for the 'cancel' action.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function cancel(array $form, FormStateInterface $form_state) {
    $this->getTempstore()->delete($this->machine_name);
    $form_state->setRedirect('entity.wordpress_migration.collection');
  }

  /**
   * {@inheritdoc}
   */
  public function finish(array &$form, FormStateInterface $form_state): void {
    if (!$form_state->getTemporaryValue(['wizard', 'id'])) {
      // Workaround to prevent the parent wizard class from trying to set the
      // ID as a string. Since it's a content entity, the ID is numeric.
      $form_state->setTemporaryValue(['wizard', 'id'], NULL);
    }
    parent::finish($form, $form_state);
  }

}
