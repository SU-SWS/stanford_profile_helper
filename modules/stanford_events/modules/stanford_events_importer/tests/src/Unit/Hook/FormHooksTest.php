<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events_importer\Unit\Hook;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\stanford_events_importer\Hook\FormHooks;
use Drupal\stanford_migrate\StanfordMigrateInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for FormHooks.
 */
#[Group('stanford_events_importer')]
#[CoversClass(FormHooks::class)]
class FormHooksTest extends UnitTestCase {

  /**
   * The mocked current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $currentUser;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->currentUser = $this->createMock(AccountProxyInterface::class);

    // t() calls produce TranslatableMarkup objects. Casting those to a
    // string (as several assertions below do) requires a string
    // translation service on the container.
    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * Builds the hook object with the mocked current user.
   */
  protected function buildHooks(): FormHooks {
    return new FormHooks($this->currentUser);
  }

  /**
   * The form alter adds a fieldset with the "Save & Import" submit button
   * and the "Update Org & Category Options" button, gated by permission.
   */
  public function testFormConfigPagesStanfordEventsImporterFormAlterWithPermission(): void {
    $this->currentUser->method('hasPermission')
      ->with('administer migrations')
      ->willReturn(TRUE);

    $form = ['actions' => []];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = $this->buildHooks()->formConfigPagesStanfordEventsImporterFormAlter($form, $form_state);

    $this->assertSame($form, $result);
    $this->assertEquals('fieldset', $result['actions']['#type']);
    $this->assertEquals(99, $result['actions']['#weight']);

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

    $updateOpts = $result['actions']['update_opts'];
    $this->assertEquals('submit', $updateOpts['#type']);
    $this->assertEquals('Update Org & Category Options', (string) $updateOpts['#value']);
    $this->assertEquals('op', $updateOpts['#name']);
    $this->assertSame([[$this->buildHooks()::class, 'updateOpts']], $updateOpts['#submit']);
    $this->assertTrue($updateOpts['#access']);
  }

  /**
   * Without the "administer migrations" permission, the update options
   * button is inaccessible.
   */
  public function testFormConfigPagesStanfordEventsImporterFormAlterWithoutPermission(): void {
    $this->currentUser->method('hasPermission')
      ->with('administer migrations')
      ->willReturn(FALSE);

    $form = ['actions' => []];
    $form_state = $this->createMock(FormStateInterface::class);

    $result = $this->buildHooks()->formConfigPagesStanfordEventsImporterFormAlter($form, $form_state);

    $this->assertFalse($result['actions']['update_opts']['#access']);
  }

  /**
   * The static submit handler clears migration caches and runs the import.
   */
  public function testImportSubmit(): void {
    $container = new ContainerBuilder();

    $migrationManager = $this->createMock(MigrationPluginManager::class);
    $migrationManager->expects($this->once())->method('clearCachedDefinitions');
    $container->set('plugin.manager.migration', $migrationManager);

    $cacheTagsInvalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $cacheTagsInvalidator->expects($this->once())
      ->method('invalidateTags')
      ->with([
        'config:migrate_plus.migration.stanford_localist_importer',
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
      ->with('stanford_localist_importer');
    $container->set('stanford_migrate', $migrateService);

    \Drupal::setContainer($container);

    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    FormHooks::importSubmit($form, $form_state);
  }

}
