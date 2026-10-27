<?php

namespace Drupal\Tests\stanford_courses_importer\Unit\Plugin\migrate\source;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\DataParserPluginInterface;
use Drupal\migrate_plus\DataParserPluginManager;
use Drupal\stanford_courses_importer\Plugin\migrate\source\ExploreCoursesUrl;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the ExploreCourses url source plugin.
 */
#[Group('stanford_courses_importer')]
class ExploreCoursesUrlTest extends UnitTestCase {

  /**
   * Url values on the config page.
   *
   * @var array|null
   */
  protected $configPageUrls;

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
      ->with('stanford_courses_importer', 'su_explore_course_url', [], 'uri')
      ->willReturnCallback(fn() => $this->configPageUrls);

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
      'data_parser_plugin' => 'simple_xml',
      'fields' => [],
      'ids' => ['guid' => ['type' => 'string']],
    ];
    $plugin = new ExploreCoursesUrl($configuration, 'explore_courses_url', [], $migration, $parser_manager, $config_pages);
    $plugin->getDataParserPlugin();
    $this->assertEquals(implode(', ', $this->parserConfiguration['urls']), (string) $plugin);
    return $this->parserConfiguration['urls'];
  }

  /**
   * The urls come from the ExploreCourses field on the config page.
   */
  public function testUrls() {
    $this->assertSame([], $this->getParserUrls());

    $this->configPageUrls = [];
    $this->assertSame([], $this->getParserUrls());

    $this->configPageUrls = ['http://example2.com', 'http://example1.com'];
    $this->assertSame(['http://example2.com', 'http://example1.com'], $this->getParserUrls());
  }

}
