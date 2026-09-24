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
use PHPUnit\Framework\Attributes\Group;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\stanford_events_importer\StanfordEventsImporter;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;

/**
 * Unit tests for FormHooks.
 */
#[Group('stanford_events_importer')]
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

  /**
   * The org and category options are fetched and cached.
   */
  public function testUpdateOpts(): void {
    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->expects($this->exactly(2))
      ->method('set')
      ->willReturnCallback(function ($cid, $data, $expire, $tags) {
        $expected = [
          StanfordEventsImporter::CACHE_KEY_CAT => ['0' => 'Arts'],
          StanfordEventsImporter::CACHE_KEY_ORG => ['279' => 'AASA'],
        ];
        $this->assertArrayHasKey($cid, $expected);
        $this->assertEquals($expected[$cid], $data);
        $this->assertEquals(CacheBackendInterface::CACHE_PERMANENT, $expire);
        $this->assertEquals(['stanford_events_importer'], $tags);
      });

    $messenger = $this->createMock(MessengerInterface::class);
    $messenger->expects($this->once())
      ->method('addStatus')
      ->with('Updated category and organization information.');
    $messenger->expects($this->never())->method('addWarning');

    $this->setUpdateOptsContainer($this->getFeedClient(), $cache, $messenger);
    FormHooks::updateOpts();
  }

  /**
   * A feed that can't be reached keeps the existing cached options.
   */
  public function testUpdateOptsFeedUnavailable(): void {
    $client = $this->createMock(ClientInterface::class);
    $client->method('request')
      ->willThrowException(new RequestException('Failure', new Request('GET', 'test')));

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->expects($this->never())->method('set');

    $messenger = $this->createMock(MessengerInterface::class);
    $messenger->expects($this->never())->method('addStatus');
    $messenger->expects($this->once())
      ->method('addWarning')
      ->with('Unable to update category and organization information.');

    $this->setUpdateOptsContainer($client, $cache, $messenger);
    FormHooks::updateOpts();
  }

  /**
   * Get a mocked http client that returns the category and org feeds.
   */
  protected function getFeedClient(): ClientInterface {
    $client = $this->createMock(ClientInterface::class);
    $client->method('request')
      ->willReturnCallback(function ($method, $url, $options) {
        $body = isset($options['query']['category-list'])
          ? '<CategoryList><Category><guid>0</guid><name>Arts</name></Category></CategoryList>'
          : '<OrganizationList><Organization><guid>279</guid><name>AASA</name></Organization></OrganizationList>';
        return new Response(200, [], $body);
      });
    return $client;
  }

  /**
   * Put the services used by the static updateOpts() on the container.
   */
  protected function setUpdateOptsContainer(ClientInterface $client, CacheBackendInterface $cache, MessengerInterface $messenger): void {
    $container = new ContainerBuilder();
    $container->set('http_client', $client);
    $container->set('cache.default', $cache);
    $container->set('messenger', $messenger);
    \Drupal::setContainer($container);
  }

}
