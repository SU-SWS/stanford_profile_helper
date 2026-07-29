<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_courses_importer\Unit\Hook;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\stanford_courses_importer\Hook\FormHooks;
use Drupal\stanford_migrate\StanfordMigrateInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for FormHooks.
 */
#[Group('stanford_courses_importer')]
#[CoversClass(FormHooks::class)]
class FormHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_courses_importer\Hook\FormHooks
   */
  protected FormHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new FormHooks();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * The form alter adds a fieldset and the "Save & Import" submit button.
   */
  public function testFormConfigPagesStanfordCoursesImporterFormAlter() {
    $form = ['actions' => []];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = $this->hooks->formConfigPagesStanfordCoursesImporterFormAlter($form, $form_state);

    $this->assertSame($form, $result);
    $this->assertEquals('fieldset', $result['actions']['#type']);
    $this->assertArrayHasKey('import', $result['actions']);

    $import = $result['actions']['import'];
    $this->assertEquals('submit', $import['#type']);
    $this->assertEquals('Save & Import', (string) $import['#value']);
    $this->assertEquals('op', $import['#name']);
    $this->assertEquals('primary', $import['#button_type']);
    $this->assertSame([
      '::submitForm',
      '::save',
      [FormHooks::class, 'importSubmit'],
    ], $import['#submit']);
  }

  /**
   * The static submit handler clears migration caches and runs the import.
   */
  public function testImportSubmit() {
    $container = new ContainerBuilder();

    $migrationManager = $this->createMock(MigrationPluginManager::class);
    $migrationManager->expects($this->once())->method('clearCachedDefinitions');
    $container->set('plugin.manager.migration', $migrationManager);

    $cacheTagsInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with([
        'config:migrate_plus.migration.stanford_courses_importer',
        'migration_plugins',
      ]);
    $container->set('cache_tags.invalidator', $cacheTagsInvalidator);

    $migrateService = $this->createMock(StanfordMigrateInterface::class);
    $migrateService->expects($this->once())
      ->method('setBatchExecution')
      ->with(TRUE)
      ->willReturnSelf();
    $migrateService->expects($this->once())
      ->method('executeMigrationId')
      ->with('stanford_courses');
    $container->set('stanford_migrate', $migrateService);

    \Drupal::setContainer($container);

    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    FormHooks::importSubmit($form, $form_state);
  }

}
