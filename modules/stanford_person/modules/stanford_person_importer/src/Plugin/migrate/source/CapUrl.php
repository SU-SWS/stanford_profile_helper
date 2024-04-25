<?php

namespace Drupal\stanford_person_importer\Plugin\migrate\source;

use Drupal\Core\Url;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_plus\Plugin\migrate\source\Url as UrlPlugin;

/**
 * Source plugin for retrieving data via CAP URLs.
 *
 * @MigrateSource(
 *   id = "cap_url"
 * )
 */
class CapUrl extends UrlPlugin {

  const URL_CHUNKS = 15;

  /**
   * Cap service.
   *
   * @var \Drupal\stanford_person_importer\CapInterface
   */
  protected $cap;

  protected $configPages;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration);

    $this->cap = \Drupal::service('stanford_person_importer.cap');
    $this->configPages = \Drupal::service('@config_pages.loader');
    $this->configFactory = \Drupal::configFactory();
    $this->entityTypeManager = \Drupal::entityTypeManager();

    $this->sourceUrls = $this->getImporterUrls();
  }


  /**
   * Get a list of urls for the importer.
   *
   * @return array|null
   *   Array of urls or NULL if any errors occur.
   */
  protected function getImporterUrls(): ?array {
    $urls = &drupal_static('cap_source_urls');
    if ($urls !== NULL) {
      return $urls;
    }
    try {
      $this->cap->setClientId($this->getCapClientId());
      $this->cap->setClientSecret($this->getCapClientSecret());

      $urls = $this->getOrgsUrls();
      $urls = array_merge($urls, $this->getWorkgroupUrls());
      $urls = array_merge($urls, $this->getSunetUrls());
    }
    catch (\Exception $e) {
      return NULL;
    }

    $allowed_fields = $this->getAllowedFields();
    foreach ($urls as &$url) {
      $url = Url::fromUri($url);
      $url->mergeOptions(['query' => ['whitelist' => implode(',', $allowed_fields)]]);
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
  protected function getAllowedFields() {
    $allowed_fields = $this->configFactory->getEditable('migrate_plus.migration.su_stanford_person')
      ->getOriginal('source.fields') ?: [];
    foreach ($allowed_fields as &$field) {
      $field = $field['selector'];
      if ($slash_position = strpos($field, '/')) {
        $field = substr($field, 0, $slash_position);
      }
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
  protected function getOrgsUrls() {
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
    foreach ($org_tids as &$tid) {
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