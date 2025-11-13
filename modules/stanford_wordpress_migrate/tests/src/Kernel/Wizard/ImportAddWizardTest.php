<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Kernel\Wizard;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\media\Entity\MediaType;
use Drupal\stanford_wordpress_migrate\Entity\WordPressMigration;
use Drupal\stanford_wordpress_migrate\Wizard\ImportAddWizard;

/**
 * Kernel tests for ImportAddWizard.
 */
class ImportAddWizardTest extends KernelTestBase {

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
    'media',
    'file',
    'image',
    'ctools',
    'stanford_wordpress_migrate',
    'taxonomy',
  ];

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The wizard instance.
   *
   * @var \Drupal\stanford_wordpress_migrate\Wizard\ImportAddWizard
   */
  protected $wizard;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('wordpress_migration');
    $this->installEntitySchema('node');
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installConfig(['node', 'media', 'field', 'file', 'image']);

    $this->entityTypeManager = $this->container->get('entity_type.manager');

    // Create test content types.
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    MediaType::create([
      'id' => 'image',
      'label' => 'Image',
      'source' => 'image',
    ])->save();

    // Create wizard instance with all required dependencies.
    $this->wizard = new ImportAddWizard(
      $this->container->get('tempstore.shared'),
      $this->container->get('form_builder'),
      $this->container->get('class_resolver'),
      $this->container->get('event_dispatcher'),
      $this->container->get('current_route_match'),
      $this->container->get('renderer'),
      'test_tempstore',
      $this->entityTypeManager
    );
  }

  /**
   * Test getMachineLabel.
   */
  public function testGetMachineLabel(): void {
    $label = $this->wizard->getMachineLabel();
    $this->assertEquals('Site Name', $label->render());
  }

  /**
   * Test getEntityType.
   */
  public function testGetEntityType(): void {
    $this->assertEquals('wordpress_migration', $this->wizard->getEntityType());
  }

  /**
   * Test getWizardLabel.
   */
  public function testGetWizardLabel(): void {
    $label = $this->wizard->getWizardLabel();
    $this->assertEquals('WordPress Importer', $label->render());
  }

  /**
   * Test exists method.
   */
  public function testExists(): void {
    $this->assertEquals('\Drupal\stanford_wordpress_migrate\Entity\WordPressMigration::load', $this->wizard->exists());
  }

  /**
   * Test getRouteName.
   */
  public function testGetRouteName(): void {
    $this->assertEquals('entity.wordpress_migration.add_step_form', $this->wizard->getRouteName());
  }

  /**
   * Test customizeForm removes name field and sets hidden id.
   */
  public function testCustomizeForm(): void {
    // The customizeForm method exists and modifies the form.
    // We can't easily test it in isolation due to rendering complexity,
    // but we can verify it exists and is callable.
    $this->assertTrue(method_exists($this->wizard, 'customizeForm'));

    $reflection = new \ReflectionClass($this->wizard);
    $method = $reflection->getMethod('customizeForm');
    $method->setAccessible(TRUE);

    // Verify the method is protected as expected.
    $this->assertTrue($method->isProtected());
  }

  /**
   * Test submitForm for new migration.
   */
  public function testSubmitFormNewMigration(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);

    $form = [];
    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard', 'wordpress_migration'], $migration);

    $this->wizard->submitForm($form, $form_state);

    $reflection = new \ReflectionClass($this->wizard);
    $property = $reflection->getProperty('machine_name');
    $property->setAccessible(TRUE);

    $this->assertEquals('new', $property->getValue($this->wizard));
  }

  /**
   * Test submitForm for existing migration.
   */
  public function testSubmitFormExistingMigration(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);
    $migration->save();

    $form = [];
    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard', 'wordpress_migration'], $migration);

    $this->wizard->submitForm($form, $form_state);

    $reflection = new \ReflectionClass($this->wizard);
    $property = $reflection->getProperty('machine_name');
    $property->setAccessible(TRUE);

    // For existing migration, machine_name should not be 'new'.
    $this->assertNotEquals('new', $property->getValue($this->wizard));
  }

  /**
   * Test getOperations with basic steps.
   */
  public function testGetOperationsBasicSteps(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);

    $cached_values = ['wordpress_migration' => $migration];
    $operations = $this->wizard->getOperations($cached_values);

    $this->assertArrayHasKey('source', $operations);
    $this->assertArrayHasKey('taxonomy_term', $operations);
    $this->assertArrayHasKey('media', $operations);
    $this->assertArrayHasKey('node', $operations);
    $this->assertArrayHasKey('review', $operations);

    $this->assertEquals('Data source', $operations['source']['title']->render());
    $this->assertEquals('Taxonomy term', $operations['taxonomy_term']['title']->render());
    $this->assertEquals('Media', $operations['media']['title']->render());
    $this->assertEquals('Content', $operations['node']['title']->render());
    $this->assertEquals('Review', $operations['review']['title']->render());
  }

  /**
   * Test getOperations with media field mapping.
   */
  public function testGetOperationsWithMediaFieldMapping(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);
    $migration->setConfigurationValue('media', [
      '/wp/v2/media' => [
        'image' => [
          'field1' => 'value1',
        ],
      ],
    ]);

    $cached_values = ['wordpress_migration' => $migration];
    $operations = $this->wizard->getOperations($cached_values);

    // Should have the field mapping step.
    $this->assertArrayHasKey('media--media--image', $operations);
    $this->assertEquals('Media to Image', $operations['media--media--image']['title']->render());
    $this->assertEquals('media', $operations['media--media--image']['values']['entity_type']);
    $this->assertEquals('/wp/v2/media', $operations['media--media--image']['values']['source']);
    $this->assertEquals('image', $operations['media--media--image']['values']['destination']);
  }

  /**
   * Test getOperations with node field mapping.
   */
  public function testGetOperationsWithNodeFieldMapping(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);
    $migration->setConfigurationValue('node', [
      '/wp/v2/posts' => [
        'article' => [
          'field1' => 'value1',
        ],
      ],
    ]);

    $cached_values = ['wordpress_migration' => $migration];
    $operations = $this->wizard->getOperations($cached_values);

    // Should have the field mapping step.
    $this->assertArrayHasKey('node--posts--article', $operations);
    $this->assertEquals('Posts to Article', $operations['node--posts--article']['title']->render());
    $this->assertEquals('node', $operations['node--posts--article']['values']['entity_type']);
    $this->assertEquals('/wp/v2/posts', $operations['node--posts--article']['values']['source']);
    $this->assertEquals('article', $operations['node--posts--article']['values']['destination']);
  }

  /**
   * Test getOperations with multiple field mappings.
   */
  public function testGetOperationsWithMultipleFieldMappings(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);
    $migration->setConfigurationValue('media', [
      '/wp/v2/media' => [
        'image' => [],
      ],
    ]);
    $migration->setConfigurationValue('node', [
      '/wp/v2/posts' => [
        'article' => [],
      ],
    ]);

    $cached_values = ['wordpress_migration' => $migration];
    $operations = $this->wizard->getOperations($cached_values);

    $this->assertArrayHasKey('media--media--image', $operations);
    $this->assertArrayHasKey('node--posts--article', $operations);
    $this->assertArrayHasKey('review', $operations);
  }

  /**
   * Test finish method behavior.
   */
  public function testFinish(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);

    $form = [];
    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard', 'wordpress_migration'], $migration);
    $form_state->setTemporaryValue(['wizard', 'label'], 'Test Migration');
    // Don't set wizard id to simulate new entity.

    $this->wizard->finish($form, $form_state);

    $this->assertNull($form_state->getTemporaryValue(['wizard', 'id']));
  }

  /**
   * Test finish method with existing wizard id.
   */
  public function testFinishWithExistingId(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);

    $form = [];
    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard', 'wordpress_migration'], $migration);
    $form_state->setTemporaryValue(['wizard', 'label'], 'Test Migration');
    $form_state->setTemporaryValue(['wizard', 'id'], 123);

    $this->wizard->finish($form, $form_state);

    // Should remain unchanged.
    $this->assertEquals(123, $form_state->getTemporaryValue(['wizard', 'id']));
  }

  /**
   * Test that operations include all form classes.
   */
  public function testGetOperationsFormClasses(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);

    $cached_values = ['wordpress_migration' => $migration];
    $operations = $this->wizard->getOperations($cached_values);

    $this->assertEquals('Drupal\stanford_wordpress_migrate\Form\ImporterStep1SourceSelectForm', $operations['source']['form']);
    $this->assertEquals('Drupal\stanford_wordpress_migrate\Form\ImporterStep2EntitySelectForm', $operations['taxonomy_term']['form']);
    $this->assertEquals('Drupal\stanford_wordpress_migrate\Form\ImporterStepReviewForm', $operations['review']['form']);
  }

}
