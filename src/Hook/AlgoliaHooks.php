<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\search_api\IndexInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;

class AlgoliaHooks {

  public function __construct(
    #[Autowire(service: 'config_pages.loader')]
    protected ConfigPagesLoaderServiceInterface $configPagesLoader,
    protected RequestStack $requestStack,
    protected EntityTypeManagerInterface $entityTypeManager
  ) {}

  /**
   * Implements hook_ENTITY_TYPE_update().
   */
  #[Hook('node_update')]
  public function nodeUpdate(NodeInterface $node) {
    if (
      !$node->isPublished() &&
      $node->getOriginal()?->isPublished()
    ) {
      self::clearAlgolia($node);
    }
  }

  /**
   * Clear the algolia index item immediately.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node to delete.
   */
  protected static function clearAlgolia(NodeInterface $node) {
    if (\Drupal::hasService('search_api_algolia.helper')) {
      \Drupal::service('search_api_algolia.helper')->entityDelete($node);
    }
  }

  /**
   * Implements hook_search_api_algolia_objects_alter().
   */
  #[Hook('search_api_algolia_objects_alter')]
  public function alterObjects(array &$objects, IndexInterface $index, array $items) {
    // If the canonical url is set, use that to adjust the urls.
    $site_domain = $this->getSiteDomain();
    $current_host = $this->getCurrentHost();
    $federated = $this->isFederatedSearch();

    foreach ($objects as &$item) {
      $item['html'] = '';
      // Move title to the beginning for more easy UI results.
      $item = ['title' => $item['title'], ...$item];

      // If the site is configured for federated search, there's a chance that
      // two or more sites have the same UUID on the node if the sites were ever
      // cloned. To prevent possible uuid conflicts, prefix the unique ID with a
      // hash of the site name. Each site should have a unique site name since
      // that is used for the refinement options.
      if ($federated) {
        $prefix = substr(md5($item['site_name']), 0, 5) . ':';
        $item['objectID'] = $prefix . $item['objectID'];
      }

      // Remove fields that aren't necessary.
      unset($item['search_api_datasource'], $item['status']);

      foreach ($item as $name => &$field) {
        // For filter fields, structure the data to work for Algolia facets.
        // @see https://www.algolia.com/doc/api-reference/widgets/hierarchical-menu/react
        if (str_starts_with($name, 'filters_')) {
          // SearchAPI doesn't allow us to have multiple fields with the same
          // key. But we will only ever have 1 filters field on each item. So
          // make the key the same for easier re-use on the UI.
          $item['filters'] = $this->adjustFiltersData($field);
          unset($item[$name]);
        }

        // Data that is being sent as the taxonomy term names should always be
        // sent as an array of strings. When the node is only configured with one
        // term in the field, it tries to send it as a string. So we force to be
        // an array.
        $property_path = $index->getField($name)?->getPropertyPath() ?: '';
        if (is_string($field) && str_contains($property_path, ':entity:name')) {
          $field = [$field];
        }

        // Either the canonical url hasn't been set, or it matches the current
        // request. It would match the current request when the event is happening
        // in the UI. If cron is running, the current host won't match the canonical
        // url.
        if (
          $site_domain &&
          $site_domain != $current_host &&
          is_string($field) &&
          str_contains($field, $current_host)
        ) {
          // Change the urls from the current host to the canonical url.
          $field = str_replace($current_host, $site_domain, $field);
        }
      }
    }
  }

  /**
   * Build a structured data for hierarchical menu with algolia.
   *
   * @see https://www.algolia.com/doc/api-reference/widgets/hierarchical-menu/react
   *
   * @param array|string $data
   *   Either the term id or an array of term ids.
   *
   * @return array
   *   Indexed array of term structure.
   */
  protected function adjustFiltersData($data): array {
    $data = is_array($data) ? $data : [$data];
    $structured_data = [];
    $termStorage = $this->entityTypeManager->getStorage('taxonomy_term');

    $terms = $termStorage->loadMultiple($data);
    /** @var \Drupal\taxonomy\TermInterface $term */
    foreach ($terms as $term) {
      $categories = [];

      $itemData = [
        'objectId' => $term->uuid(),
        'name' => $term->label(),
      ];

      $level = 0;

      while (($parent_id = $term?->get('parent')?->getString()) && $level < 3) {
        $parent = $termStorage->load($parent_id);
        if ($parent) {
          $lastLevel = isset($categories['categories.lvl' . $level - 1]) ? $categories['categories.lvl' . $level - 1] . ' > ' : '';
          $categories["categories.lvl$level"] = $lastLevel . $parent->label();
          $term = $parent;
        }
        $level++;
      }

      $structured_data[] = [...$itemData, ... $categories];
    }
    return $structured_data;
  }

  /**
   * Get the configured canonical site domain.
   *
   * @return string|null
   *   Canonical domain.
   *
   * @codeCoverageIgnore
   */
  protected function getSiteDomain(): ?string {
    return $this->configPagesLoader->getValue('stanford_basic_site_settings', 'su_site_url', 0, 'uri');
  }

  /**
   * Is the site configured to use a federated search.
   *
   * @return bool
   *   Is federated search.
   *
   * @codeCoverageIgnore
   */
  protected function isFederatedSearch(): bool {
    return (bool) $this->configPagesLoader->getValue('stanford_basic_site_settings', 'su_site_algolia_fed', 0, 'value');
  }

  /**
   * Get the current host of the site.
   *
   * @return string|null
   *   Current request host.
   *
   * @codeCoverageIgnore
   */
  protected function getCurrentHost(): ?string {
    return $this->requestStack->getCurrentRequest()?->getSchemeAndHttpHost();
  }

}
