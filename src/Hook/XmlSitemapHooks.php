<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;

/**
 * Hooks that relate to the xmlsitemap module.
 */
class XmlSitemapHooks {

  /**
   * XmlSitemap hook constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory, protected StateInterface $state) {}

  /**
   * Alter the data of a sitemap link before the link is saved.
   *
   * @param array $link
   *   An array with the data of the sitemap link.
   * @param array $context
   *   An optional context array containing data related to the link.
   */
  #[Hook('xmlsitemap_link_alter')]
  public function xmlsitemapLinkAlter(array &$link, array $context): void {
    // Get node/[:id] from loc.
    $node_id = $link['loc'];

    // Get 403 page path.
    $page_403 = $this->configFactory->get('system.site')->get('page.403');

    // Get 404 page path.
    $page_404 = $this->configFactory->get('system.site')->get('page.404');

    // If node id matches 403 or 404 pages, remove it from sitemap.
    switch ($node_id) {
      case $page_403:
      case $page_404:
        // Status is set to zero to exclude the item in the sitemap.
        $link['status'] = 0;
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_load().
   */
  #[Hook('xmlsitemap_load')]
  public function xmlsitemapLoad($entities): void {
    /** @var \Drupal\xmlsitemap\XmlSitemapInterface $xml_entity */
    foreach ($entities as $xml_entity) {
      // When loading the XML sitemaps, set the uri to include the base url.
      // This will fix cron google submissions as well as decoupled site
      // scenarios.
      $uri = $xml_entity->get('uri');
      $base_url = $this->state->get('xmlsitemap_base_url');
      $uri['path'] = $base_url . $uri['path'];
      $xml_entity->set('uri', $uri);
    }
  }

}
