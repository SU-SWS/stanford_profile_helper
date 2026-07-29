<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_wordpress_migrate\Entity\WordPressMigration;
use Drupal\stanford_wordpress_migrate\Form\ImporterStep1SourceSelectForm;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for ImporterStep1SourceSelectForm.
 */
#[RunTestsInSeparateProcesses]
class ImporterStep1SourceSelectFormTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'user',
    'migrate',
    'stanford_wordpress_migrate',
  ];

  /**
   * Test form instance.
   *
   * @var \Drupal\stanford_wordpress_migrate\Form\ImporterStep1SourceSelectForm
   */
  protected $form;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('wordpress_migration');

    $this->form = ImporterStep1SourceSelectForm::create($this->container);
  }

  /**
   * Test form ID.
   */
  public function testGetFormId(): void {
    $this->assertEquals('entity.wordpress_migration.step_1', $this->form->getFormId());
  }

  /**
   * Test buildForm.
   */
  public function testBuildForm(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], ['wordpress_migration' => $migration]);

    $form = $this->form->buildForm([], $form_state);

    $this->assertArrayHasKey('label', $form);
    $this->assertArrayHasKey('base_url', $form);
    $this->assertEquals('Test Migration', $form['label']['#default_value']);
    $this->assertEquals('https://example.com', $form['base_url']['#default_value']);
    $this->assertTrue($form['base_url']['#disabled']);
  }

  /**
   * Test buildForm with new migration.
   */
  public function testBuildFormNewMigration(): void {
    $migration = WordPressMigration::create([
      'label' => '',
      'base_url' => '',
    ]);

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], ['wordpress_migration' => $migration]);

    $form = $this->form->buildForm([], $form_state);

    $this->assertArrayHasKey('label', $form);
    $this->assertArrayHasKey('base_url', $form);
    $this->assertEquals('', $form['label']['#default_value']);
    $this->assertEquals('', $form['base_url']['#default_value']);
    $this->assertFalse($form['base_url']['#disabled']);
  }

  /**
   * Test validateForm with invalid URL.
   */
  public function testValidateFormInvalidUrl(): void {
    $form = [
      'base_url' => [
        '#type' => 'textfield',
        '#parents' => ['base_url'],
      ],
    ];

    $form_state = new FormState();
    $form_state->setValue('base_url', 'not-a-url');

    $this->form->validateForm($form, $form_state);

    $errors = $form_state->getErrors();
    $this->assertNotEmpty($errors);
  }

  /**
   * Test validateForm with non-external URL.
   */
  public function testValidateFormNonExternalUrl(): void {
    $form = [
      'base_url' => [
        '#type' => 'textfield',
        '#parents' => ['base_url'],
      ],
    ];

    $form_state = new FormState();
    $form_state->setValue('base_url', '/relative/path');

    $this->form->validateForm($form, $form_state);

    $errors = $form_state->getErrors();
    $this->assertNotEmpty($errors);
  }

  /**
   * Test validateForm with valid WordPress site.
   */
  public function testValidateFormValidWordPressSite(): void {
    $mockResponse = new Response(200, [], json_encode([
      'routes' => [
        '/wp/v2/posts' => [],
        '/wp/v2/pages' => [],
      ],
    ]));

    $mock = new MockHandler([$mockResponse]);
    $handlerStack = HandlerStack::create($mock);
    $mockClient = new Client(['handler' => $handlerStack]);

    $form = ImporterStep1SourceSelectForm::create($this->container);
    // Inject mock client via reflection.
    $reflection = new \ReflectionClass($form);
    $property = $reflection->getProperty('client');
    $property->setAccessible(TRUE);
    $property->setValue($form, $mockClient);

    $form_array = [
      'base_url' => [
        '#type' => 'textfield',
        '#parents' => ['base_url'],
      ],
    ];

    $form_state = new FormState();
    $form_state->setValue('base_url', 'https://example.com');

    $form->validateForm($form_array, $form_state);

    $errors = $form_state->getErrors();
    $this->assertEmpty($errors);
    $this->assertNotEmpty($form_state->get('wp-api-routes'));
  }

  /**
   * Test submitForm.
   */
  public function testSubmitForm(): void {
    $migration = WordPressMigration::create([
      'label' => 'Old Label',
      'base_url' => 'https://old.com',
    ]);

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], ['wordpress_migration' => $migration]);
    $form_state->setValue('label', 'New Label');
    $form_state->setValue('base_url', 'https://new.com');
    $form_state->set('wp-api-routes', ['/wp/v2/posts' => 'Posts']);

    $form = [];
    $this->form->submitForm($form, $form_state);

    $temp_values = $form_state->getTemporaryValue(['wizard']);
    $this->assertEquals('New Label', $temp_values['wordpress_migration']->label());
    $this->assertEquals('https://new.com', $temp_values['wordpress_migration']->getBaseUrl());
    $this->assertEquals(['/wp/v2/posts' => 'Posts'], $temp_values['api-routes']);
  }

}
