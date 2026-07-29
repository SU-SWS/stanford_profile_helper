<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Block\BlockPluginInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use Drupal\stanford_profile_helper\StanfordProfileHelper;
use Drupal\taxonomy_menu\Plugin\Menu\TaxonomyMenuMenuLink;

/**
 * Hooks that relate to menu links and their caching.
 */
class MenuHooks {

  use StringTranslationTrait;

  /**
   * Menu hook constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current active user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(protected AccountProxyInterface $currentUser, protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Implements hook_menu_links_discovered_alter().
   */
  #[Hook('menu_links_discovered_alter')]
  public function menuLinksDiscoveredAlter(&$links): void {
    if (isset($links['admin_toolbar_tools.extra_links:node.add.stanford_page'])) {
      $links['admin_toolbar_tools.extra_links:node.add.stanford_page']['weight'] = -99;
    }
    if (isset($links['admin_toolbar_tools.extra_links:media_page'])) {
      // Alter the "Media" link for /admin/content/media path.
      $links['admin_toolbar_tools.extra_links:media_page']['title'] = $this->t('All Media');
    }
    if (isset($links['system.admin_content'])) {
      // Change the node list page for the /admin/content path.
      $links['system.admin_content']['title'] = $this->t('All Content');
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_menu')]
  public function preprocessMenu(&$variables): void {
    if ($variables['menu_name'] == 'admin') {
      $this->checkAdminMenuAccess($variables['items']);
    }

    $cache_tags = $variables['#cache']['tags'] ?? [];
    foreach ($variables['items'] as &$item) {
      // Taxonomy menu link items use the description from the term as the
      // title attribute. The description can be very long and could contain
      // HTML. To make things easiest, just remove the title attribute.
      if ($item['original_link'] instanceof TaxonomyMenuMenuLink) {
        $attributes = $item['url']->getOption('attributes');
        unset($attributes['title']);
        $item['url']->setOption('attributes', $attributes);

        $term = $this->entityTypeManager->getStorage('taxonomy_term')
          ->load($item['url']->getRouteParameters()['taxonomy_term']);

        if ($term) {
          $cache_tags[] = 'taxonomy_term_list:' . $term->bundle();
          $cache_tags = array_merge($cache_tags, $term->getCacheTags());
        }
      }
    }
    $variables['#cache']['tags'] = array_unique($cache_tags);
  }

  /**
   * Check the access for certain admin menu items and remove them if needed.
   *
   * @param array $menu_items
   *   Keyed array of menu item from preprocess_menu.
   */
  protected function checkAdminMenuAccess(array &$menu_items): void {
    foreach ($menu_items as $key => &$item) {
      /** @var \Drupal\Core\Url $url */
      $url = $item['url'];

      $vid = $url->getRouteParameters()['taxonomy_vocabulary'] ?? FALSE;
      if (
        $vid &&
        !$this->currentUser->hasPermission('administer taxonomy') &&
        !$this->currentUser->hasPermission("create terms in $vid") &&
        !$this->currentUser->hasPermission("delete terms in $vid") &&
        !$this->currentUser->hasPermission("edit terms in $vid")
      ) {
        unset($menu_items[$key]);
        continue;
      }

      $this->checkAdminMenuAccess($item['below']);
    }
  }

  /**
   * Implements hook_block_build_alter().
   */
  #[Hook('block_build_alter')]
  public function blockBuildAlter(array &$build, BlockPluginInterface $block): void {
    if ($block->getBaseId() == 'system_menu_block') {
      $build['#cache']['tags'][] = 'stanford_profile_helper:menu_links';
      StanfordProfileHelper::removeCacheTags($build, [
        '^node:*',
        '^config:system.menu.*',
      ]);
    }
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_block__system_main_block')]
  public function preprocessBlockSystemMainBlock(&$variables): void {
    $variables['content']['#cache']['tags'][] = 'stanford_profile_helper:menu_links';
    // Remove node cache tags since we'll use our own cache tag above.
    StanfordProfileHelper::removeCacheTags($variables['content'], ['^config:system.menu.*']);
  }

  /**
   * Implements hook_preprocess_HOOK().
   */
  #[Hook('preprocess_block__system_menu_block')]
  public function preprocessBlockSystemMenuBlock(&$variables): void {
    $variables['content']['#cache']['tags'][] = 'stanford_profile_helper:menu_links';
    // Remove node cache tags since we'll use our own cache tag above.
    StanfordProfileHelper::removeCacheTags($variables['content'], [
      '^node:*',
      '^config:system.menu.*',
    ]);
  }

  /**
   * Implements hook_entity_trash_delete().
   */
  #[Hook('entity_trash_delete')]
  public function entityTrashDelete(EntityInterface $entity): void {
    // If a node has menu link data, delete the menu link.
    if (
      $entity instanceof NodeInterface &&
      $entity->hasField('field_menulink') &&
      !$entity->get('field_menulink')->isEmpty()
    ) {
      \Drupal::database()->delete('menu_tree')
        ->condition('id', 'menu_link_field:%', 'LIKE')
        ->condition('route_param_key', 'node=' . $entity->id())
        ->execute();
      \Drupal::service('router.builder')->rebuildIfNeeded();
      StanfordProfileHelper::clearMenuCacheTag();
    }
  }

}
