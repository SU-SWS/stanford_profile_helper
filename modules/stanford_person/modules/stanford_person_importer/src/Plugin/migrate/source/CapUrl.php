<?php

namespace Drupal\stanford_person_importer\Plugin\migrate\source;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\migrate\Attribute\MigrateSource;
use Drupal\migrate\Exception\RequirementsException;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Plugin\RequirementsInterface;
use Drupal\migrate_plus\DataParserPluginInterface;
use Drupal\migrate_plus\DataParserPluginManager;
use Drupal\stanford_migrate\Plugin\migrate\source\StanfordUrl;
use Drupal\stanford_person_importer\CapInterface;

/**
 * Source plugin that builds the CAP API urls from the importer config page.
 *
 * The urls and OAuth credentials come from the "stanford_person_importer"
 * config page. The urls are only built when the data is actually fetched, so
 * loading the migration doesn't call the CAP API.
 *
 * Example:
 *
 * @code
 * source:
 *   plugin: cap_url
 *   data_fetcher_plugin: http
 *   data_parser_plugin: json
 *   authentication:
 *     plugin: oauth2
 *     grant_type: client_credentials
 *     base_uri: 'https://authz.stanford.edu'
 *     token_url: /oauth/token
 * @endcode
 */
#[MigrateSource(id: 'cap_url')]
class CapUrl extends StanfordUrl implements RequirementsInterface {

  /**
   * How many profiles are returned in each url.
   */
  const URL_CHUNKS = 15;

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
      $container->get('stanford_person_importer.cap'),
      $container->get('config_pages.loader'),
      $container->get('entity_type.manager'),
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
    protected CapInterface $cap,
    protected ConfigPagesLoaderServiceInterface $configPages,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    // The urls are populated once the data parser is needed.
    $configuration['urls'] = [];
    $configuration['authentication']['client_id'] = $this->getCapClientId();
    $configuration['authentication']['client_secret'] = $this->getCapClientSecret();
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $parser_plugin_manager);
  }

  /**
   * {@inheritDoc}
   */
  public function checkRequirements() {
    if (!$this->getCapClientId() || !$this->getCapClientSecret()) {
      throw new RequirementsException('CAP API credentials are not configured.');
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getDataParserPlugin(): DataParserPluginInterface {
    if (!isset($this->dataParserPlugin)) {
      $this->sourceUrls = $this->getImporterUrls();
      $this->configuration['urls'] = $this->sourceUrls;
    }
    return parent::getDataParserPlugin();
  }

  /**
   * Get a list of urls for the importer.
   *
   * @return string[]
   *   Array of CAP API urls.
   *
   * @throws \Drupal\migrate\MigrateException
   */
  protected function getImporterUrls(): array {
    try {
      $this->cap->setClientId($this->getCapClientId());
      $this->cap->setClientSecret($this->getCapClientSecret());

      $urls = [
        ...$this->getOrgsUrls(),
        ...$this->getWorkgroupUrls(),
        ...$this->getSunetUrls(),
      ];
    }
    catch (\Exception $e) {
      // Fail the import so the orphan actions don't remove every profile.
      throw new MigrateException('Unable to build the CAP API urls: ' . $e->getMessage());
    }

    $allowed_fields = implode(',', array_unique($this->getAllowedFields()));
    foreach ($urls as &$url) {
      $url = Url::fromUri($url);
      $url->mergeOptions(['query' => ['whitelist' => $allowed_fields]]);
      $url = $url->toString(TRUE)->getGeneratedUrl();
    }
    return $urls;
  }

  /**
   * Get a list of the fields from CAP that should be fetched.
   *
   * @return string[]
   *   Array of CAP selectors.
   */
  protected function getAllowedFields(): array {
    $allowed_fields = [];
    foreach ($this->configuration['fields'] ?? [] as $field) {
      $allowed_fields[] = strtok($field['selector'], '/');
    }
    return $allowed_fields;
  }

  /**
   * Get a list of CAP urls that have an org code specified.
   *
   * @return string[]
   *   List of urls.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getOrgsUrls(): array {
    $org_tids = array_filter($this->configPages->getValue('stanford_person_importer', 'su_person_orgs', [], 'target_id') ?? []);
    $include_children = (bool) $this->configPages->getValue('stanford_person_importer', 'su_person_child_orgs', 0, 'value');

    // No field values populated.
    if (empty($org_tids)) {
      return [];
    }
    $org_codes = [];

    // Load the taxonomy term that the field is pointing at and use the org code
    // field on the term entity.
    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    foreach ($org_tids as $tid) {
      if ($term = $term_storage->load($tid)) {
        $org_code = $term->get('su_cap_org_code')
          ->getString();
        $org_codes[] = str_replace(' ', '', $org_code);
      }
    }
    return $this->getUrlChunks($this->cap->getOrganizationUrl($org_codes, $include_children));
  }

  /**
   * Get a list of CAP urls that have a workgroup filter.
   *
   * @return string[]
   *   List of urls.
   */
  protected function getWorkgroupUrls(): array {
    $workgroups = array_filter($this->configPages->getValue('stanford_person_importer', 'su_person_workgroup', [], 'value') ?? []);

    if ($workgroups) {
      return $this->getUrlChunks($this->cap->getWorkgroupUrl($workgroups));
    }
    return [];
  }

  /**
   * Get the list of CAP urls for a sunetid filter.
   *
   * @return string[]
   *   List of urls.
   */
  protected function getSunetUrls(): array {
    $sunets = $this->configPages->getValue('stanford_person_importer', 'su_person_sunetid', [], 'value') ?: [];

    $urls = [];
    foreach (array_chunk($sunets, self::URL_CHUNKS) as $chunk) {
      $urls[] = $this->cap->getSunetUrl($chunk)->toString(TRUE)->getGeneratedUrl();
    }
    return $urls;
  }

  /**
   * Break up the url into multiple urls based on the number of results.
   *
   * @param \Drupal\Core\Url $url
   *   Cap API Url.
   *
   * @return string[]
   *   Array of Cap API Urls.
   */
  protected function getUrlChunks(Url $url): array {
    $count = $this->cap->getTotalProfileCount($url);
    $number_chunks = ceil($count / self::URL_CHUNKS);
    if ($number_chunks <= 1) {
      $url->mergeOptions(['query' => ['ps' => self::URL_CHUNKS]]);
      return [$url->toString(TRUE)->getGeneratedUrl()];
    }

    $urls = [];
    for ($i = 1; $i <= $number_chunks; $i++) {
      $url->mergeOptions(['query' => ['p' => $i, 'ps' => self::URL_CHUNKS]]);
      $urls[] = $url->toString(TRUE)->getGeneratedUrl();
    }
    return $urls;
  }

  /**
   * Get the username from the config pages field.
   *
   * @return string
   *   Client ID string.
   */
  protected function getCapClientId(): string {
    return $this->configPages->getValue('stanford_person_importer', 'su_person_cap_username', 0, 'value') ?? '';
  }

  /**
   * Get the password from the config pages field.
   *
   * @return string
   *   Client secret string.
   */
  protected function getCapClientSecret(): string {
    return $this->configPages->getValue('stanford_person_importer', 'su_person_cap_password', 0, 'value') ?? '';
  }

}
