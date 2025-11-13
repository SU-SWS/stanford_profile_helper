<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\stanford_wordpress_migrate\Entity\WordPressMigration;
use Drupal\stanford_wordpress_migrate\Form\ImporterStep5FieldMappingForm;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Kernel tests for ImporterStep5FieldMappingForm.
 */
class ImporterStep5FieldMappingFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'text',
    'user',
    'migrate',
    'node',
    'stanford_wordpress_migrate',
  ];

  /**
   * Test form instance.
   *
   * @var \Drupal\stanford_wordpress_migrate\Form\ImporterStep5FieldMappingForm
   */
  protected $form;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('wordpress_migration');
    $this->installEntitySchema('node');
    $this->installConfig(['node', 'field']);

    // Create test node type.
    NodeType::create([
      'type' => 'test_content',
      'name' => 'Test Content',
    ])->save();

    $this->form = ImporterStep5FieldMappingForm::create($this->container);
  }

  /**
   * Test form ID.
   */
  public function testGetFormId(): void {
    $this->assertEquals('entity.wordpress_migration.step_4', $this->form->getFormId());
  }

  /**
   * Test buildForm.
   */
  public function testBuildForm(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);

    // Mock the HTTP client response for source fields.
    $mockResponse = new Response(200, [], json_encode([
      [
        'id' => 1,
        'title' => ['rendered' => 'Test Post'],
        'content' => ['rendered' => 'Content'],
      ],
    ]));

    $mock = new MockHandler([$mockResponse]);
    $handlerStack = HandlerStack::create($mock);
    $mockClient = new Client(['handler' => $handlerStack]);

    $form = ImporterStep5FieldMappingForm::create($this->container);
    // Inject mock client via reflection.
    $reflection = new \ReflectionClass($form);
    $property = $reflection->getProperty('client');
    $property->setAccessible(TRUE);
    $property->setValue($form, $mockClient);

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], [
      'wordpress_migration' => $migration,
      'entity_type' => 'node',
      'source' => '/wp/v2/posts',
      'destination' => 'test_content',
      // Pre-set api-routes to avoid the API call in parent buildForm.
      'api-routes' => ['/wp/v2/posts' => 'Posts'],
    ]);

    $result = $form->buildForm([], $form_state);

    $this->assertArrayHasKey('field_mapping', $result);
    $this->assertArrayHasKey('add_more', $result);
    $this->assertEquals('node', $form_state->get('entity_type'));
    $this->assertEquals('/wp/v2/posts', $form_state->get('source'));
    $this->assertEquals('test_content', $form_state->get('destination'));
  }

  /**
   * Test submitForm saves field mappings.
   */
  public function testSubmitForm(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], ['wordpress_migration' => $migration]);
    $form_state->set('entity_type', 'node');
    $form_state->set('source', '/wp/v2/posts');
    $form_state->set('destination', 'test_content');
    $form_state->setValue('field_mapping', [
      [
        'source' => 'title.rendered',
        'destination_settings' => [
          'destination' => 'title',
          'settings' => "plugin: default_value\ndefault_value: test",
        ],
        'weight' => 0,
      ],
      [
        'source' => '',
        'destination_settings' => [
          'destination' => '',
          'settings' => '',
        ],
        'weight' => 1,
      ],
    ]);

    $form = [];
    $this->form->submitForm($form, $form_state);

    $config = $migration->getConfigurationValue(['node', '/wp/v2/posts', 'test_content']);
    $this->assertCount(1, $config);
    $this->assertEquals('title.rendered', $config[0]['source']);
    $this->assertEquals('title', $config[0]['destination']);
  }

  /**
   * Test validateCustomProcessSettings.
   */
  public function testValidateCustomProcessSettings(): void {
    $element = [
      '#value' => "invalid: yaml: :\n  - broken",
      '#parents' => ['field_mapping', 0, 'destination_settings', 'settings'],
    ];

    $form_state = new FormState();
    $form_state->setValue(['field_mapping', 0, 'destination_settings', 'settings'], "invalid: yaml: :\n  - broken");
    $form = [];

    ImporterStep5FieldMappingForm::validateCustomProcessSettings($element, $form_state, $form);

    $errors = $form_state->getErrors();
    $this->assertNotEmpty($errors);
  }

  /**
   * Test validateCustomProcessSettings with valid YAML.
   */
  public function testValidateCustomProcessSettingsValid(): void {
    $element = [
      '#value' => "plugin: default_value\ndefault_value: test",
      '#parents' => ['field_mapping', 0, 'destination_settings', 'settings'],
    ];

    $form_state = new FormState();
    $form = [];

    ImporterStep5FieldMappingForm::validateCustomProcessSettings($element, $form_state, $form);

    $errors = $form_state->getErrors();
    $this->assertEmpty($errors);
  }

  /**
   * Test settingsEditSubmit.
   */
  public function testSettingsEditSubmit(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setTriggeringElement([
      '#parents' => ['field_mapping', 0, 'settings'],
      '#op' => 'edit',
    ]);

    ImporterStep5FieldMappingForm::settingsEditSubmit($form, $form_state);

    $this->assertTrue($form_state->get(['plugin_settings_edit', 0]));
    $this->assertTrue($form_state->isRebuilding());
  }

  /**
   * Test settingsEditSubmit with update operation.
   */
  public function testSettingsEditSubmitUpdate(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->setTriggeringElement([
      '#parents' => ['field_mapping', 0, 'destination_settings', 'settings_save'],
      '#op' => 'update',
    ]);
    $form_state->set(['plugin_settings_edit', 0], TRUE);

    ImporterStep5FieldMappingForm::settingsEditSubmit($form, $form_state);

    // The default case sets it to FALSE, not NULL.
    $this->assertFalse($form_state->get(['plugin_settings_edit', 0]));
    $this->assertTrue($form_state->isRebuilding());
  }

  /**
   * Test settingsEditAjax.
   */
  public function testSettingsEditAjax(): void {
    $form = [
      'field_mapping' => [
        '#type' => 'table',
      ],
    ];

    $form_state = new FormState();

    $result = ImporterStep5FieldMappingForm::settingsEditAjax($form, $form_state);

    $this->assertEquals($form['field_mapping'], $result);
  }

  /**
   * Test destinationChangedAjax.
   */
  public function testDestinationChangedAjax(): void {
    $form = [
      'field_mapping' => [
        0 => [
          'destination_settings' => [
            '#type' => 'container',
          ],
        ],
      ],
    ];

    $form_state = new FormState();
    $form_state->setTriggeringElement([
      '#parents' => ['field_mapping', 0, 'destination_settings', 'destination'],
    ]);

    $result = ImporterStep5FieldMappingForm::destinationChangedAjax($form, $form_state);

    $this->assertEquals($form['field_mapping'][0]['destination_settings'], $result);
  }

}
