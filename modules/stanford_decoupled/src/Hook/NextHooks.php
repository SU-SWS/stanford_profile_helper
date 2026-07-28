<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\stanford_decoupled\Config\DecoupledConfigOverrides;

/**
 * Hooks that support the Next.js integration.
 */
class NextHooks {

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Implements hook_ENTITY_TYPE_insert().
   *
   * When a new Next site is created, create all Next entity type configs.
   */
  #[Hook('next_site_insert')]
  public function nextSiteInsert(EntityInterface $entity): void {
    Cache::invalidateTags(['library_info']);
    $next_storage = $this->entityTypeManager->getStorage('next_entity_type_config');
    $node_types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();

    // Create each of the node type bundle configs.
    foreach (array_keys($node_types) as $node_bundle) {
      // Make sure one doesn't already exist.
      if (!$next_storage->load("node.$node_bundle")) {
        $next_storage->create([
          'id' => "node.$node_bundle",
          'site_resolver' => 'site_selector',
          'revalidator' => 'path',
          'draft_enabled' => TRUE,
          'configuration' => [
            'sites' => [$entity->id() => $entity->id()],
          ],
          'revalidator_configuration' => [
            'revalidate_page' => TRUE,
            'additional_paths' => "/tags/views:all/views:$node_bundle",
            'method' => 'POST',
            'aggregate' => TRUE,
          ],
        ])->save();
      }
    }

    if (!$next_storage->load('redirect.redirect')) {
      $next_storage->create([
        'id' => 'redirect.redirect',
        'site_resolver' => 'site_selector',
        'revalidator' => 'redirect_path',
        'draft_enabled' => FALSE,
        'configuration' => ['sites' => [$entity->id() => $entity->id()]],
        'revalidator_configuration' => [
          'revalidate_page' => TRUE,
          'additional_paths' => '',
          'method' => 'POST',
          'aggregate' => TRUE,
        ],
      ])->save();
    }

    if (!$next_storage->load('menu_link_content.menu_link_content')) {
      $next_storage->create([
        'id' => 'menu_link_content.menu_link_content',
        'site_resolver' => 'site_selector',
        'revalidator' => 'path',
        'draft_enabled' => FALSE,
        'configuration' => ['sites' => [$entity->id() => $entity->id()]],
        'revalidator_configuration' => [
          'revalidate_page' => FALSE,
          'additional_paths' => '/tags/menu:main',
          'method' => 'POST',
          'aggregate' => TRUE,
        ],
      ])->save();
    }

    $config_page_types = [
      'lockup_settings',
      'stanford_global_message',
      'stanford_local_footer',
      'stanford_basic_site_settings',
      'stanford_super_footer',
    ];
    // Create each of the node type bundle configs.
    foreach ($config_page_types as $config_page_type) {
      // Make sure one doesn't already exist.
      if (!$next_storage->load("config_pages.$config_page_type")) {
        $next_storage->create([
          'id' => "config_pages.$config_page_type",
          'site_resolver' => 'site_selector',
          'revalidator' => 'path',
          'draft_enabled' => FALSE,
          'configuration' => [
            'sites' => [$entity->id() => $entity->id()],
          ],
          'revalidator_configuration' => [
            'revalidate_page' => FALSE,
            'additional_paths' => "/tags/config-pages",
            'method' => 'POST',
            'aggregate' => TRUE,
          ],
        ])->save();
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('redirect_access')]
  public function redirectAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    if ($operation == 'view' && DecoupledConfigOverrides::isDecoupled()) {
      // Allowing viewing redirecting from JSON API.
      return AccessResult::allowed();
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_next_site_preview_alter().
   */
  #[Hook('next_site_preview_alter')]
  public function nextSitePreviewAlter(array &$preview, array $context): void {
    // Only use the preview for nodes. Prevent the preview from any other entity
    // type that might have a revalidation configured, like redirects.
    if ($context['entity']->getEntityTypeid() != 'node') {
      $preview = $context['original_build'][0]['content'];
    }

    if (isset($preview['toolbar']['links']['#links']['live_link']['url'])) {
      /** @var \Drupal\Core\Url $url */
      $url = $preview['toolbar']['links']['#links']['live_link']['url'];
      $options = $url->getOptions();
      // No need for all the other parameters.
      if (isset($options['query']['slug'])) {
        $options['query'] = [
          'slug' => $options['query']['slug'],
          'secret' => $options['query']['secret'],
        ];
        $url->setOptions($options);
      }
    }
  }

}
