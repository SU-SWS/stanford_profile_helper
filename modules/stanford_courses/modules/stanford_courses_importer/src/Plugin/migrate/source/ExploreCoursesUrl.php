<?php

namespace Drupal\stanford_courses_importer\Plugin\migrate\source;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\DataParserPluginManager;
use Drupal\stanford_migrate\Plugin\migrate\source\StanfordUrl;

/**
 * Source plugin that uses the ExploreCourses urls from the config page.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: explore_courses_url
 *   data_fetcher_plugin: http
 *   data_parser_plugin: simple_xml
 * @endcode
 */
#[MigrateSource(id: 'explore_courses_url')]
class ExploreCoursesUrl extends StanfordUrl {

  /**
   * {@inheritDoc}
   */
  public static function create($container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('plugin.manager.migrate_plus.data_parser'),
      $container->get('config_pages.loader'),
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    MigrationInterface $migration,
    DataParserPluginManager $parser_plugin_manager,
    protected ConfigPagesLoaderServiceInterface $configPages,
  ) {
    $configuration['urls'] = $this->configPages->getValue('stanford_courses_importer', 'su_explore_course_url', [], 'uri') ?? [];
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $parser_plugin_manager);
  }

}
