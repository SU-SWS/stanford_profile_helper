<?php

namespace Drupal\Tests\stanford_events_importer\Unit\Plugin\migrate\source;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\DataParserPluginInterface;
use Drupal\migrate_plus\DataParserPluginManager;
use Drupal\stanford_events_importer\Plugin\migrate\source\LocalistUrl;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the Localist url source plugin.
 */
#[Group('stanford_events_importer')]
class LocalistUrlTest extends UnitTestCase {

  /**
   * Config page field values keyed by field name.
   *
   * @var array
   */
  protected $configPagesValues = [];

  /**
   * Configuration the data parser plugin was created with.
   *
   * @var array
   */
  protected $parserConfiguration;

  /**
   * Get the urls the source plugin passes to the data parser.
   */
  protected function getParserUrls(): array {
    $config_pages = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $config_pages->method('getValue')
      ->willReturnCallback(fn($type, $field_name) => $this->configPagesValues[$field_name] ?? NULL);

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
      'fields' => [],
      'ids' => ['id' => ['type' => 'integer']],
    ];
    $plugin = new LocalistUrl($configuration, 'localist_url', [], $migration, $parser_manager, $config_pages);
    $plugin->getDataParserPlugin();
    $this->assertEquals(implode(', ', $this->parserConfiguration['urls']), (string) $plugin);
    return $this->parserConfiguration['urls'];
  }

  /**
   * The urls come from the bookmark and feed fields on the config page.
   */
  public function testUrls() {
    $this->assertSame([], $this->getParserUrls());

    $this->configPagesValues = ['su_localist_bookmark' => ['http://example1.com']];
    $this->assertSame(['http://example1.com'], $this->getParserUrls());

    $this->configPagesValues = ['su_localist_url' => ['http://example2.com']];
    $this->assertSame(['http://example2.com'], $this->getParserUrls());

    $this->configPagesValues = [
      'su_localist_url' => ['http://example3.com'],
      'su_localist_bookmark' => ['http://example4.com'],
    ];
    $this->assertSame(['http://example3.com', 'http://example4.com'], $this->getParserUrls());

    $this->configPagesValues = [
      'su_localist_url' => ['http://example6.com'],
      'su_localist_bookmark' => ['http://example5.com'],
    ];
    $this->assertSame(['http://example5.com', 'http://example6.com'], $this->getParserUrls());
  }

}
