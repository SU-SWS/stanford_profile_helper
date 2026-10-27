<?php

namespace Drupal\Tests\stanford_person_importer\Unit\Plugin\migrate\source;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssembler;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\DataParserPluginInterface;
use Drupal\migrate_plus\DataParserPluginManager;
use Drupal\stanford_person_importer\CapInterface;
use Drupal\stanford_person_importer\Plugin\migrate\source\CapUrl;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Test the CAP url source plugin.
 */
#[Group('stanford_person_importer')]
class CapUrlTest extends UnitTestCase {

  /**
   * Incrementing profile count the mock CAP service returns.
   *
   * @var int
   */
  protected $count = 0;

  /**
   * Config page field values keyed by field name.
   *
   * @var array
   */
  protected $configPageValues = [];

  /**
   * If the cap api mock service should throw an error.
   *
   * @var bool
   */
  protected $sunetUrlError = FALSE;

  /**
   * Configuration the data parser plugin was created with.
   *
   * @var array
   */
  protected $parserConfiguration;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $path_processor = $this->createMock(OutboundPathProcessorInterface::class);
    $unrouted_assembler = new UnroutedUrlAssembler($request_stack, $path_processor);

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('unrouted_url_assembler', $unrouted_assembler);
    \Drupal::setContainer($container);
  }

  /**
   * Get the source plugin with mocked services.
   */
  protected function getPlugin(): CapUrl {
    $config_pages = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $config_pages->method('getValue')
      ->willReturnCallback(fn($type, $field_name) => $this->configPageValues[$field_name] ?? NULL);

    $field_items = $this->createMock(FieldItemListInterface::class);
    $field_items->method('getString')->willReturn('foo, bar');

    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('get')->willReturn($field_items);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('load')->willReturn($entity);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    $cap = $this->createMock(CapInterface::class);
    $cap->method('getTotalProfileCount')
      ->willReturnCallback(fn() => $this->count += 10);
    $cap->method('getOrganizationUrl')
      ->willReturn(Url::fromUri('http://localhost.orgs'));
    $cap->method('getWorkgroupUrl')
      ->willReturn(Url::fromUri('http://localhost.workgroup'));
    $cap->method('getSunetUrl')
      ->willReturnCallback([$this, 'getSunetUrlCallback']);

    $migration = $this->createMock(MigrationInterface::class);
    $migration->method('getIdMap')
      ->willReturn($this->createMock(MigrateIdMapInterface::class));

    $parser_manager = $this->createMock(DataParserPluginManager::class);
    $parser_manager->method('createInstance')
      ->willReturnCallback(function ($plugin_id, $configuration) {
        $this->parserConfiguration = $configuration;
        return $this->createMock(DataParserPluginInterface::class);
      });

    $configuration = [
      'data_parser_plugin' => 'json',
      'authentication' => ['plugin' => 'oauth2'],
      'fields' => [['selector' => 'fooBar'], ['selector' => 'barFoo/baz']],
      'ids' => ['sunetid' => ['type' => 'string']],
    ];
    return new CapUrl($configuration, 'cap_url', [], $migration, $parser_manager, $cap, $config_pages, $entity_type_manager);
  }

  /**
   * Missing credentials fail the requirements check.
   */
  public function testRequirements() {
    $this->expectException(RequirementsException::class);
    $this->getPlugin()->checkRequirements();
  }

  /**
   * The credentials are added to the authentication configuration.
   */
  public function testCredentials() {
    $this->configPageValues = [
      'su_person_cap_username' => 'foo',
      'su_person_cap_password' => 'bar',
    ];
    $plugin = $this->getPlugin();
    $plugin->checkRequirements();
    $plugin->getDataParserPlugin();

    $this->assertEquals('foo', $this->parserConfiguration['authentication']['client_id']);
    $this->assertEquals('bar', $this->parserConfiguration['authentication']['client_secret']);
    $this->assertEmpty($this->parserConfiguration['urls']);
  }

  /**
   * No filters configured means there are no urls.
   */
  public function testEmptyUrls() {
    $this->getPlugin()->getDataParserPlugin();
    $this->assertSame([], $this->parserConfiguration['urls']);
  }

  /**
   * The data parser receives the urls from the config page.
   */
  public function testUrls() {
    $this->configPageValues = [
      'su_person_cap_username' => 'foo',
      'su_person_cap_password' => 'bar',
      'su_person_orgs' => [1, 2],
      'su_person_workgroup' => ['foo:bar'],
      'su_person_sunetid' => ['foobar'],
    ];
    $plugin = $this->getPlugin();
    $plugin->getDataParserPlugin();

    $expected_urls = [
      'http://localhost.orgs?ps=15&whitelist=fooBar,barFoo',
      'http://localhost.workgroup?p=1&ps=15&whitelist=fooBar,barFoo',
      'http://localhost.workgroup?p=2&ps=15&whitelist=fooBar,barFoo',
      'http://localhost.sunet?whitelist=fooBar,barFoo',
    ];
    $this->assertEquals($expected_urls, array_map('urldecode', $this->parserConfiguration['urls']));
    $this->assertEquals(implode(', ', $this->parserConfiguration['urls']), (string) $plugin);
  }

  /**
   * An API error stops the import instead of providing no urls.
   */
  public function testUrlError() {
    $this->configPageValues = ['su_person_sunetid' => ['foobar']];
    $this->sunetUrlError = TRUE;
    $this->expectException(MigrateException::class);
    $this->getPlugin()->getDataParserPlugin();
  }

  /**
   * Cap mock service callback.
   */
  public function getSunetUrlCallback() {
    if ($this->sunetUrlError) {
      throw new \Exception('Error getting sunet url');
    }
    return Url::fromUri('http://localhost.sunet');
  }

}
