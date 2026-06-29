<?php

namespace Drupal\stanford_wordpress_migrate\Form;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Serialization\Yaml;
use Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginManager;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple wizard step form.
 */
class ImporterStep5FieldMappingForm extends WordPressImporterFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity.wordpress_migration.step_4';
  }

  /**
   * Constructs a new ImporterStep5Form.
   *
   * @param \GuzzleHttp\ClientInterface $client
   *   Guzzle client service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entityFieldManager
   *   Entity field manager service.
   * @param \Drupal\stanford_wordpress_migrate\WordPressMigrateFieldProcessorPluginManager $fieldProcessorPluginManager
   *   Field process plugin manager service.
   */
  public function __construct(
    protected ClientInterface $client,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected WordPressMigrateFieldProcessorPluginManager $fieldProcessorPluginManager,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): ImporterStep5FieldMappingForm|static {
    return new static(
      $container->get('http_client'),
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager'),
      $container->get('plugin.manager.wordpress_migrate_field_processor')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $cached_values = $form_state->getTemporaryValue(['wizard']);

    /** @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration */
    $migration = $cached_values['wordpress_migration'];

    if (isset($cached_values['source'])) {
      // Store the values to persist through ajax calls.
      $form_state->set('entity_type', $cached_values['entity_type']);
      $form_state->set('source', $cached_values['source']);
      $form_state->set('destination', $cached_values['destination']);
    }

    $destinationFields = $this->getDestinationFields($cached_values['entity_type'], $cached_values['destination']);
    $sourceFields = $this->getSourceFields($migration->getBaseUrl(), $cached_values['source']);

    $form['field_mapping'] = [
      '#type' => 'table',
      '#tree' => TRUE,
      '#caption' => $this->t('View the raw JSON data: <a href="@url" target="_blank">%source</a>', [
        '@url' => $migration->getBaseUrl() . '/wp-json' . $cached_values['source'],
        '%source' => $cached_values['source'],
      ]),
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'field-mapping-weight',
        ],
      ],
      '#header' => [
        $this->t('Source'),
        ['data' => $this->t('Destination'), 'colspan' => '3'],
      ],
      '#prefix' => '<div id="content-mapping">',
      '#suffix' => '</div>',
      '#attached' => ['library' => ['stanford_wordpress_migrate/edit-form']],
    ];

    $settings = $migration->getConfigurationValue([
      $cached_values['entity_type'],
      $cached_values['source'],
      $cached_values['destination'],
    ], []);

    $num_mappings = $form_state->get('num_mappings');
    if ($num_mappings === NULL) {
      $num_mappings = count($settings) ?: 1;
      $form_state->set('num_mappings', $num_mappings);
    }

    for ($i = 0; $i < $num_mappings; $i++) {
      $form['field_mapping'][$i] = $this->buildRow($form, $form_state, $i, $migration, $sourceFields, $destinationFields, $settings[$i] ?? []);
    }

    $form['add_more'] = [
      '#type' => 'submit',
      '#value' => t('Add Another'),
      '#submit' => [[self::class, 'addAnother']],
      '#ajax' => [
        'callback' => [self::class, 'addAnotherAjax'],
        'wrapper' => 'content-mapping',
      ],
      '#add-more' => 'field_mapping',
    ];
    return $form;
  }

  /**
   * Build a row for the editing draggable table.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param int $delta
   *   Row delta.
   * @param \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration
   *   Migration content entity.
   * @param array $sourceFields
   *   Indexed array of source field paths.
   * @param array $destinationFields
   *   Keyed array of supported entity fields.
   * @param array $settings
   *   Current settings on the row.
   *
   * @return array
   *   Row render array.
   */
  protected function buildRow(array $form, FormStateInterface $form_state, int $delta, WordPressMigrationInterface $migration, array $sourceFields, array $destinationFields, array $settings = []) {
    $submitValues = $form_state->getValue(['field_mapping', $delta], []);
    $element['#attributes']['class'][] = 'draggable';
    $element['source'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Source Field'),
      '#default_value' => $submitValues['source'] ?? $settings['source'] ?? NULL,
      '#autocomplete_route_name' => 'stanford_wordpress_migrate.autocomplete.sources',
      '#autocomplete_route_parameters' => [
        'sources' => $sourceFields,
      ],
    ];

    $element['destination_settings'] = [
      '#type' => 'container',
      '#cell_attributes' => ['colspan' => 2],
      '#prefix' => "<div id='destination-settings-$delta'>",
      '#suffix' => '</div>',
    ];

    $editSettings = (bool) $form_state->get(['plugin_settings_edit', $delta]);
    if ($trigger = $form_state->getTriggeringElement()) {
      if (end($trigger['#parents']) == 'destination' && $trigger['#parents'][1] == $delta) {
        unset($submitValues['destination_settings']['settings'], $settings['settings']);
      }
    }

    $destField = $submitValues['destination_settings']['destination'] ?? $settings['destination'] ?? NULL;
    $defaultProcess = [];
    // A destination field has been chosen, fetch the process plugin info.
    if ($destField) {
      $defaultProcess = $this->getFieldProcessPluginConfig($migration, $form_state->get('entity_type'), $form_state->get('destination'), $destField);
    }

    $processConfig = $submitValues['destination_settings']['settings'] ?? $settings['settings'] ?? $defaultProcess;

    // Show the text area for custom settings if the user clicked the "Edit"
    // button.
    if ($editSettings) {
      $element['destination_settings']['settings'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Custom Process'),
        '#default_value' => Yaml::encode($processConfig),
        '#element_validate' => [[self::class, 'validateCustomProcessSettings']],
      ];

      $element['destination_settings']['settings_save'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
        '#op' => 'update',
        '#submit' => [[self::class, 'settingsEditSubmit']],
        '#ajax' => [
          'callback' => [self::class, 'settingsEditAjax'],
          'wrapper' => 'content-mapping',
        ],
      ];
    }
    else {
      // Encode the custom process plugins into a hidden value. That way we
      // don't have to save the value in the form state storage.
      $element['destination_settings']['settings'] = [
        '#type' => 'hidden',
        '#value' => is_array($processConfig) ? Yaml::encode($processConfig) : $processConfig,
      ];
    }

    $element['destination_settings']['destination'] = [
      '#type' => 'select',
      '#title' => $this->t('Destination Field'),
      '#empty_option' => $this->t('- None -'),
      '#options' => $destinationFields,
      '#default_value' => $destField,
      '#access' => !$editSettings,
      '#ajax' => [
        'callback' => [self::class, 'destinationChangedAjax'],
        'wrapper' => "destination-settings-$delta",
      ],
    ];

    $element['settings'] = [
      '#type' => 'image_button',
      '#name' => $delta . '_settings_edit',
      '#src' => 'core/misc/icons/787878/cog.svg',
      '#attributes' => [
        'alt' => $this->t('Edit'),
      ],
      '#op' => 'edit',
      '#prefix' => "<div id='plugin-settings-$delta'>",
      '#suffix' => '</div>',
      '#access' => !$editSettings,
      '#submit' => [[self::class, 'settingsEditSubmit']],
      '#ajax' => [
        'callback' => [self::class, 'settingsEditAjax'],
        'wrapper' => 'content-mapping',
      ],
    ];

    $element['weight'] = [
      '#type' => 'weight',
      '#title' => $this->t('Weight'),
      '#title_display' => 'invisible',
      '#size' => 4,
      '#default_value' => $delta,
      '#attributes' => ['class' => ['field-mapping-weight']],
    ];
    return $element;
  }

  /**
   * Field validation for custom process plugin settings.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted state.
   * @param array $form
   *   Complete form.
   */
  public static function validateCustomProcessSettings(array $element, FormStateInterface $form_state, array $form) {
    $customSettings = $form_state->getValue($element['#parents']);
    if (!$customSettings) {
      return;
    }

    // Try to parse the YAML first. Malformed string will throw an error.
    try {
      $yaml = Yaml::decode($customSettings);
    }
    catch (\Exception $e) {
      $form_state->setError($element, 'Invalid YAML');
      return;
    }

    $yaml = isset($yaml['plugin']) ? [$yaml] : $yaml;

    /** @var \Drupal\migrate\Plugin\MigratePluginManager $processPluginManager */
    $processPluginManager = \Drupal::service('plugin.manager.migrate.process');
    foreach ($yaml as $process) {
      if (!isset($process['plugin'])) {
        $form_state->setError($element, t('Invalid process configuration: Plugin not set.'));
      }

      if (!$processPluginManager->hasDefinition($process['plugin'])) {
        $form_state->setError($element, t('Invalid plugin. Plugin @id does not exist.', ['@id' => $process['plugin']]));
      }
    }
  }

  /**
   * Ajax callback when a destination is changed.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current state of the form.
   *
   * @return array
   *    Modified element.
   */
  public static function destinationChangedAjax(array $form, FormStateInterface $form_state): array {
    $trigger = $form_state->getTriggeringElement();
    $form_state->setRebuild();
    $position = $trigger['#parents'][1];
    return $form['field_mapping'][$position]['destination_settings'];
  }

  /**
   * Form submission handler for multistep buttons.
   *
   * @param array $form
   *    Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *    Current state of the form.
   */
  public static function settingsEditSubmit(array $form, FormStateInterface $form_state): void {
    $trigger = $form_state->getTriggeringElement();
    $position = $trigger['#parents'][1];
    $op = $trigger['#op'];
    switch ($op) {
      case 'edit':
        $form_state->set(['plugin_settings_edit', $position], TRUE);
        break;

      default:
        $form_state->set(['plugin_settings_edit', $position], FALSE);
        break;
    }
    $form_state->setRebuild();
  }

  /**
   * Ajax callback for multistep buttons.
   *
   * @param array $form
   *    Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *    Current state of the form.
   */
  public static function settingsEditAjax(array $form, FormStateInterface $form_state): array {
    $form_state->setRebuild();
    return $form['field_mapping'];
  }

  /**
   * Get the available source data fields.
   *
   * @param string $baseUrl
   *   WordPress domain.
   * @param string $endpoint
   *   API endpoint.
   *
   * @return array
   *   Indexed array of field options.
   */
  protected function getSourceFields(string $baseUrl, string $endpoint): array {
    try {
      $response = $this->client->request('GET', "$baseUrl/wp-json$endpoint?per_page=1", ['timeout' => 5]);
      $response = json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);

      return array_unique($this->flattenArrayAndGetKeys($response[0]));
    }
    catch (\Throwable $e) {
      return [];
    }
  }

  /**
   * Get a list of available fields to map data into.
   *
   * @param string $entityType
   *   Entity type id.
   * @param string $bundle
   *   Entity bundle machine name.
   *
   * @return array
   *   Associative array of field names.
   */
  protected function getDestinationFields(string $entityType, string $bundle): array {
    $entityDef = $this->entityTypeManager->getDefinition($entityType);
    $bundleKey = $entityDef->getKey('bundle');
    /** @var \Drupal\Core\Entity\ContentEntityInterface $mockEntity */
    $mockEntity = $this->entityTypeManager->getStorage($entityType)
      ->create([$bundleKey => $bundle]);
    $contentFields = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);

    // Build a list of all acceptable field types we have plugins for.
    $acceptedFieldTypes = [];
    $processPlugins = $this->fieldProcessorPluginManager->getDefinitions();
    foreach ($processPlugins as $pluginDef) {
      $acceptedFieldTypes = [
        ...$acceptedFieldTypes,
        ...$pluginDef['fieldType'],
      ];
    }

    // Filter out fields that the user doesn't have access to edit.
    /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $contentFields */
    $contentFields = array_filter($contentFields, fn($field) => $mockEntity->get($field->getName())
        ->access('edit') && in_array($field->getType(), $acceptedFieldTypes));

    $fields = [];
    foreach ($contentFields as $field) {
      $fieldColumns = $field->getFieldStorageDefinition()->getColumns();
      if (count($fieldColumns) == 1) {
        $fields[$field->getName()] = $field->getLabel();
      }
      else {
        // Allow mapping data into each column on the field, such as link uri
        // vs title or long text value, format, vs summary.
        foreach (array_keys($fieldColumns) as $column) {
          $fields[sprintf('%s/%s', $field->getName(), $column)] = sprintf('%s: %s', $field->getLabel(), $column);
        }
      }
    }

    asort($fields);
    return $fields;
  }

  /**
   * Get the default process plugin settings that would be applied to the
   * field.
   *
   * @param \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration
   *   WordPress content entity.
   * @param string $entityType
   *   Entity type id.
   * @param string $bundle
   *   Destination bundle.
   * @param string $fieldName
   *   Destination field name.
   *
   * @return array|null
   *   Default process plugin settings.
   */
  protected function getFieldProcessPluginConfig(WordPressMigrationInterface $migration, string $entityType, string $bundle, string $fieldName): ?array {
    $contentFields = $this->entityFieldManager->getFieldDefinitions($entityType, $bundle);
    $field = $contentFields[preg_replace('/\/.*$/', '', $fieldName)] ?? NULL;
    if (!$field) {
      return NULL;
    }

    return $this->fieldProcessorPluginManager->getFieldPlugin($field->getType())
      ?->setWordPressMigration($migration)
      ?->getProcess($field);
  }

  /**
   * Flatten an array and return just the keys, with their respective paths.
   *
   * @param array $array
   *   Multidimensional array to flatten.
   * @param string|NULL $parentKey
   *   Parent key if nested.
   *
   * @return array
   *   Indexed array of key paths.
   */
  protected function flattenArrayAndGetKeys(array $array, ?string $parentKey = NULL): array {
    $flatKeys = [];

    foreach ($array as $key => $value) {
      if (is_numeric($key) && !is_array($value)) {
        $flatKeys[] = $parentKey;
        continue;
      }
      $currentKey = !$parentKey ? $key : $parentKey . '/' . $key;

      if (is_array($value)) {
        // Recursively call the function for nested arrays
        $flatKeys = array_merge($flatKeys, $this->flattenArrayAndGetKeys($value, $currentKey));
      }
      else {
        // Add the current key to the list
        $flatKeys[] = $currentKey;
      }
    }

    return $flatKeys;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $cached_values = $form_state->getTemporaryValue(['wizard']);
    $entity_type = $form_state->get('entity_type');
    $source = $form_state->get('source');
    $destination = $form_state->get('destination');

    /** @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration */
    $migration = $cached_values['wordpress_migration'];

    // Remove rows that don't have a configured source or destination.
    $fieldMappings = array_filter($form_state->getValue('field_mapping'), fn($mapping) => $mapping['source'] && $mapping['destination_settings']['destination']);

    foreach ($fieldMappings as &$mapping) {
      $mapping['destination'] = $mapping['destination_settings']['destination'];
      $processSettings = $mapping['destination_settings']['settings'] ?? '';

      $mapping['settings'] = Yaml::decode($processSettings);

      // If the default process settings match the settings from the form, we
      // don't need to save them.
      $defaultConfig = $this->getFieldProcessPluginConfig($migration, $entity_type, $destination, $mapping['destination']);
      if (!$mapping['settings'] || json_encode($mapping['settings']) == json_encode($defaultConfig)) {
        $mapping['settings'] = NULL;
      }

      $mapping = [
        'source' => $mapping['source'],
        'destination' => $mapping['destination'],
        'settings' => $mapping['settings'],
      ];
    }

    $migration->setConfigurationValue([
      $entity_type,
      $source,
      $destination,
    ], array_values($fieldMappings));
  }

}
