<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_wordpress_migrate\Entity\WordPressMigration;
use Drupal\stanford_wordpress_migrate\Form\ImporterStepReviewForm;

/**
 * Kernel tests for ImporterStepReviewForm.
 */
class ImporterStepReviewFormTest extends KernelTestBase {

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
   * @var \Drupal\stanford_wordpress_migrate\Form\ImporterStepReviewForm
   */
  protected $form;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('wordpress_migration');

    $this->form = ImporterStepReviewForm::create($this->container);
  }

  /**
   * Test form ID.
   */
  public function testGetFormId(): void {
    $this->assertEquals('entity.wordpress_migration.step_6', $this->form->getFormId());
  }

  /**
   * Test buildForm with no configuration.
   */
  public function testBuildFormNoConfiguration(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], ['wordpress_migration' => $migration]);

    $form = $this->form->buildForm([], $form_state);

    $this->assertIsArray($form);
    $this->assertArrayNotHasKey('taxonomy', $form);
  }

  /**
   * Test buildForm with taxonomy mappings.
   */
  public function testBuildFormWithTaxonomyMappings(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);
    $migration->setConfigurationValue('taxonomy_term', [
      '/wp/v2/categories' => ['test_vocab' => []],
      '/wp/v2/tags' => ['tags' => []],
    ]);

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], ['wordpress_migration' => $migration]);

    $form = $this->form->buildForm([], $form_state);

    $this->assertArrayHasKey('taxonomy_term', $form);
    $this->assertArrayHasKey('table', $form['taxonomy_term']);
    $this->assertArrayHasKey(0, $form['taxonomy_term']['table']);
    $this->assertArrayHasKey(1, $form['taxonomy_term']['table']);
  }

  /**
   * Test buildForm with content mappings.
   */
  public function testBuildFormWithContentMappings(): void {
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

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], ['wordpress_migration' => $migration]);

    $form = $this->form->buildForm([], $form_state);

    $this->assertArrayHasKey('node', $form);
    $this->assertArrayHasKey('table', $form['node']);
  }

  /**
   * Test buildForm with both taxonomy and content mappings.
   */
  public function testBuildFormWithAllMappings(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);
    $migration->setConfigurationValue('taxonomy_term', [
      '/wp/v2/categories' => ['test_vocab' => []],
    ]);
    $migration->setConfigurationValue('node', [
      '/wp/v2/posts' => [
        'article' => [],
      ],
    ]);
    $migration->setConfigurationValue('media', [
      '/wp/v2/media' => [
        'image' => [],
      ],
    ]);

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], ['wordpress_migration' => $migration]);

    $form = $this->form->buildForm([], $form_state);

    $this->assertArrayHasKey('taxonomy', $form);
    $this->assertArrayHasKey('node', $form);
    $this->assertArrayHasKey('media', $form);
  }

  /**
   * Test buildForm displays source and destination in table.
   */
  public function testBuildFormTableStructure(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);
    $migration->setConfigurationValue('taxonomy_term', [
     '/wp/v2/categories' => ['test_vocab' => []],
    ]);

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], ['wordpress_migration' => $migration]);

    $form = $this->form->buildForm([], $form_state);

    $this->assertEquals('/wp/v2/categories', $form['taxonomy_term']['table'][0]['source']['#markup']);
    $this->assertEquals('test_vocab', $form['taxonomy_term']['table'][0]['destination']['#markup']);
  }

  /**
   * Test buildForm with multiple content destinations.
   */
  public function testBuildFormMultipleDestinations(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);
    $migration->setConfigurationValue('node', [
      '/wp/v2/posts' => [
        'article' => [],
        'page' => [],
      ],
    ]);

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], ['wordpress_migration' => $migration]);

    $form = $this->form->buildForm([], $form_state);

    $this->assertArrayHasKey('node', $form);
    // Should have two rows for the two destinations, but the table also has a header row.
    $this->assertGreaterThanOrEqual(2, count($form['node']['table']));
  }

}
