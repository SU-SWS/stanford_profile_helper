<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Wizard;

use Drupal\stanford_wordpress_migrate\Wizard\ImportAddWizard;
use Drupal\Tests\UnitTestCase;

/**
 * Unit tests for ImportAddWizard.
 */
class ImportAddWizardTest extends UnitTestCase {

  /**
   * The wizard under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Wizard\ImportAddWizard
   */
  protected $wizard;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Mock the wizard since it extends EntityFormWizardBase which requires services
    $this->wizard = $this->getMockBuilder(ImportAddWizard::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['t'])
      ->getMock();

    $this->wizard->expects($this->any())
      ->method('t')
      ->willReturnCallback(function($string) {
        return $string;
      });
  }

  /**
   * Test wizard construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(ImportAddWizard::class, $this->wizard);
  }

  /**
   * Test getMachineLabel method.
   */
  public function testGetMachineLabel(): void {
    $result = $this->wizard->getMachineLabel();
    $this->assertEquals('Site Name', $result);
  }

  /**
   * Test wizard extends correct base class.
   */
  public function testExtendsCorrectBaseClass(): void {
    $reflection = new \ReflectionClass(ImportAddWizard::class);
    $this->assertEquals('Drupal\ctools\Wizard\EntityFormWizardBase', $reflection->getParentClass()
      ->getName());
  }

  /**
   * Test wizard uses expected form classes.
   */
  public function testUsesExpectedFormClasses(): void {
    $reflection = new \ReflectionClass(ImportAddWizard::class);
    $fileContents = file_get_contents($reflection->getFileName());

    // Check that the wizard references the expected form classes
    $this->assertStringContainsString('ImporterStep1SourceSelectForm', $fileContents);
    $this->assertStringContainsString('ImporterStep2EntitySelectForm', $fileContents);
    $this->assertStringContainsString('ImporterStep5FieldMappingForm', $fileContents);
    $this->assertStringContainsString('ImporterStepReviewForm', $fileContents);
  }

}
