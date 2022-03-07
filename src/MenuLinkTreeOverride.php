<?php

namespace Drupal\stanford_profile_helper;

use Drupal\Core\Menu\MenuLinkTreeInterface;
use Drupal\Core\Menu\MenuTreeParameters;

/**
 * Service overrider for the menu.link_tree service.
 */
class MenuLinkTreeOverride implements MenuLinkTreeInterface {

  /**
   * Original Menu Tree service.
   *
   * @var \Drupal\Core\Menu\MenuLinkTreeInterface
   */
  protected $menuTree;

  /**
   * Menu Tree service override constructor.
   *
   * @param \Drupal\Core\Menu\MenuLinkTreeInterface $menu_tree
   *   Original Menu Tree service.
   */
  public function __construct(MenuLinkTreeInterface $menu_tree) {
    $this->menuTree = $menu_tree;
  }

  /**
   * {@inheritDoc}
   */
  public function build(array $tree) {
    $build = $this->menuTree->build($tree);
    $build['#cache']['tags'][] = 'stanford_profile_helper:menu_links';
    // Remove node cache tags since we'll use our own cache tag above.
    $build['#cache']['tags'] = array_filter($build['#cache']['tags'], function ($tag) {
      return strpos($tag, 'node:') === FALSE && strpos($tag, 'config:system.menu.') === FALSE;
    });
    $build['#cache']['tags'] = array_values($build['#cache']['tags']);
    return $build;
  }

  /**
   * {@inheritDoc}
   */
  public function getCurrentRouteMenuTreeParameters($menu_name) {
    return $this->menuTree->getCurrentRouteMenuTreeParameters($menu_name);
  }

  /**
   * {@inheritDoc}
   */
  public function load($menu_name, MenuTreeParameters $parameters) {
    return $this->menuTree->load($menu_name, $parameters);
  }

  /**
   * {@inheritDoc}
   */
  public function transform(array $tree, array $manipulators) {
    return $this->menuTree->transform($tree, $manipulators);
  }

  /**
   * {@inheritDoc}
   */
  public function maxDepth() {
    return $this->menuTree->maxDepth();
  }

  /**
   * {@inheritDoc}
   */
  public function getSubtreeHeight($id) {
    return $this->menuTree->getSubtreeHeight($id);
  }

  /**
   * {@inheritDoc}
   */
  public function getExpanded($menu_name, array $parents) {
    return $this->menuTree->getExpanded($menu_name, $parents);
  }

}
