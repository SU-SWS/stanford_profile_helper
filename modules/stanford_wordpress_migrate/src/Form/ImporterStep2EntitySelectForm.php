<?php

namespace Drupal\stanford_wordpress_migrate\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple wizard step form.
 */
class ImporterStep2EntitySelectForm extends WordPressImporterFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * Step 2 form constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(private readonly EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity.wordpress_migration.step_2';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $cached_values = $form_state->getTemporaryValue(['wizard']);

    $entity_type = $cached_values['entity_type'];
    /** @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration */
    $migration = $cached_values['wordpress_migration'];

    $settings = $migration->getConfigurationValue($entity_type, []);

    $flat_settings = [];
    foreach ($settings as $source => $destinations) {
      foreach (array_keys($destinations) as $destination) {
        $flat_settings[] = [$source, $destination];
      }
    }

    $api_routes = $form_state->getTemporaryValue(['wizard', 'api-routes']);
    $bundles = $this->getAllowedBundles($entity_type);

    $form['mapping'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#header' => [
        $this->t('Source'),
        $this->t('Destination'),
      ],
      '#prefix' => '<div id="taxonomy-mapping">',
      '#suffix' => '</div>',
    ];

    $num_mappings = $form_state->get('num_mappings');
    if ($num_mappings === NULL) {
      $num_mappings = count($flat_settings) ?: 1;
      $form_state->set('num_mappings', $num_mappings);
    }

    for ($i = 0; $i < $num_mappings; $i++) {
      $form['mapping'][$i]['source'] = [
        '#type' => 'select',
        '#title' => $this->t('WordPress Entity'),
        '#empty_option' => $this->t('- None -'),
        '#options' => $api_routes,
        '#default_value' => $flat_settings[$i][0] ?? NULL,
      ];

      $form['mapping'][$i]['destination'] = [
        '#type' => 'select',
        '#title' => $this->t('Entity Bundle'),
        '#empty_option' => $this->t('- None -'),
        '#options' => $bundles,
        '#default_value' => $flat_settings[$i][1] ?? NULL,
      ];
    }

    $form['add_more'] = [
      '#type' => 'submit',
      '#value' => t('Add Another'),
      '#submit' => [[self::class, 'addAnother']],
      '#ajax' => [
        'callback' => [self::class, 'addAnotherAjax'],
        'wrapper' => 'taxonomy-mapping',
      ],
      '#add-more' => 'mapping',
    ];

    return $form;
  }

  /**
   * Get all available vocabularies the current user can create terms.
   *
   * @return array
   *   Associative array of vocabulary id and it's label.
   */
  protected function getAllowedBundles($entity_type): array {
    $entity_type_id = $this->entityTypeManager->getDefinition($entity_type)
      ->getBundleEntityType() ?? $entity_type;

    $media_types = $this->entityTypeManager->getStorage($entity_type_id)
      ->loadMultiple();
    $access_control = $this->entityTypeManager->getAccessControlHandler($entity_type_id);

    // Filter media types for only those the user can create media in.
    $media_types = array_filter($media_types, fn($type) => $access_control->createAccess($type->id()));
    $media_types = array_map(fn($type) => $type->label(), $media_types);
    asort($media_types);
    return $media_types;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration */
    $migration = $form_state->getTemporaryValue([
      'wizard',
      'wordpress_migration',
    ]);
    $media = array_filter($form_state->getValue('mapping'), fn($mapping) => $mapping['source'] && $mapping['destination']);
    $chosen_sources = array_map(fn($a) => $a['source'], $media);
    $chosen_destinations = array_map(fn($a) => $a['destination'], $media);

    $field_mapping = $migration->getConfigurationValue('media', []);

    // Remove any previously configured values that are no longer desired.
    foreach ($field_mapping as $source => $destinations) {
      $s_key = array_search($source, $chosen_sources);
      if ($s_key === FALSE) {
        unset($field_mapping[$source]);
        continue;
      }

      foreach (array_keys($destinations) as $destination) {
        if (!isset($chosen_destinations[$s_key])) {
          unset($field_mapping[$source][$destination]);
        }
      }
    }
    $migration->setConfigurationValue('media', array_filter($field_mapping));

    foreach ($media as $mapping) {
      $field_mapping = $migration->getConfigurationValue([
        'media',
        $mapping['source'],
        $mapping['destination'],
      ], []);
      $migration->setConfigurationValue([
        'media',
        $mapping['source'],
        $mapping['destination'],
      ], $field_mapping);
    }
  }

}
