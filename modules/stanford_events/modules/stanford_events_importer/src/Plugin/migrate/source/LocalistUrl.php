<?php

namespace Drupal\stanford_events_importer\Plugin\migrate\source;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\DataParserPluginManager;
use Drupal\stanford_migrate\Plugin\migrate\source\StanfordUrl;

/**
 * Source plugin that uses the Localist urls from the importer config page.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: localist_url
 *   data_fetcher_plugin: http
 *   data_parser_plugin: json
 * @endcode
 */
#[MigrateSource(id: 'localist_url')]
class LocalistUrl extends StanfordUrl {

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
    $configuration['urls'] = $this->getLocalistUrls();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $parser_plugin_manager);
  }

  /**
   * Get the bookmark and feed urls from the config page.
   *
   * @return string[]
   *   Sorted list of Localist API urls.
   */
  protected function getLocalistUrls(): array {
    $bookmark_urls = $this->configPages->getValue('stanford_events_importer', 'su_localist_bookmark', [], 'uri') ?? [];
    $feed_urls = $this->configPages->getValue('stanford_events_importer', 'su_localist_url', [], 'uri') ?? [];
    $urls = [...$bookmark_urls, ...$feed_urls];
    sort($urls);
    return array_values($urls);
  }

}
