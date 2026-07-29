<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Kernel\Wizard;

use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_wordpress_migrate\Wizard\ImportEditWizard;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for ImportEditWizard.
 */
#[RunTestsInSeparateProcesses]
class ImportEditWizardTest extends KernelTestBase {

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
   * The wizard instance.
   *
   * @var \Drupal\stanford_wordpress_migrate\Wizard\ImportEditWizard
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

    // Create wizard instance with all required dependencies.
    $this->wizard = new ImportEditWizard(
      $this->container->get('tempstore.shared'),
      $this->container->get('form_builder'),
      $this->container->get('class_resolver'),
      $this->container->get('event_dispatcher'),
      $this->container->get('current_route_match'),
      $this->container->get('renderer'),
      'test_tempstore',
      $this->container->get('entity_type.manager')
    );
  }

  /**
   * Test getRouteName returns edit route.
   */
  public function testGetRouteName(): void {
    $this->assertEquals('entity.wordpress_migration.edit_form', $this->wizard->getRouteName());
  }

  /**
   * Test that ImportEditWizard extends ImportAddWizard.
   */
  public function testExtendsImportAddWizard(): void {
    $this->assertInstanceOf('Drupal\stanford_wordpress_migrate\Wizard\ImportAddWizard', $this->wizard);
  }

  /**
   * Test inherited methods work correctly.
   */
  public function testInheritedMethods(): void {
    // Test inherited getMachineLabel.
    $label = $this->wizard->getMachineLabel();
    $this->assertEquals('Site Name', $label->render());

    // Test inherited getEntityType.
    $this->assertEquals('wordpress_migration', $this->wizard->getEntityType());

    // Test inherited getWizardLabel.
    $wizardLabel = $this->wizard->getWizardLabel();
    $this->assertEquals('WordPress Importer', $wizardLabel->render());

    // Test inherited exists method.
    $this->assertEquals('\Drupal\stanford_wordpress_migrate\Entity\WordPressMigration::load', $this->wizard->exists());
  }

}
