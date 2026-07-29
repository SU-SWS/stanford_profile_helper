<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;

/**
 * Hooks that control access to entities and fields.
 */
class AccessHooks {

  /**
   * Access hook constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   Current route match service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory, protected RouteMatchInterface $routeMatch, protected StateInterface $state) {}

  /**
   * Implements hook_entity_field_access().
   */
  #[Hook('entity_field_access')]
  public function entityFieldAccess($operation, FieldDefinitionInterface $field_definition, AccountInterface $account, ?FieldItemListInterface $items = NULL): AccessResult {
    if (
      $operation != 'view' &&
      $field_definition->getName() == 'status' &&
      $field_definition->getTargetEntityTypeId() == 'node' &&
      $items?->getEntity()?->id() &&
      !in_array('administrator', $account->getRoles())
    ) {
      // Prevent unpublishing the home, 404 and 403 pages.
      $node = $items->getEntity();
      $site_config = $this->configFactory->get('system.site');
      $node_urls = [
        $node->toUrl()->toString(TRUE)->getGeneratedUrl(),
        "/node/{$node->id()}",
      ];

      // If the node is configured to be the home page, 404, or 403, prevent
      // the user from deleting. Unfortunately this only works for roles
      // without the "Bypass content access control" permission.
      if (array_intersect($node_urls, $site_config->get('page'))) {
        return AccessResult::forbidden();
      }
    }

    if (
      $field_definition->getType() == 'entity_reference' &&
      $field_definition->getSetting('handler') == 'layout_library' &&
      $operation == 'edit'
    ) {
      $entity_type = $field_definition->getTargetEntityTypeId();
      $bundle = $field_definition->getTargetBundle();
      if (!$account->hasPermission("choose layout for $entity_type $bundle")) {
        return AccessResult::forbidden();
      }
    }

    // When the page title banner is in use on the page, disable the node
    // title field access because the field title will be used in the
    // banner.
    if (
      $operation == 'view' &&
      $field_definition->getName() == 'title' &&
      $items?->getEntity()->getEntityTypeId() == 'node' &&
      $items->getEntity()->bundle() == 'stanford_page' &&
      $items->getEntity()->get('su_page_banner')->count() &&
      $this->routeMatch->getRouteName() == 'entity.node.canonical' &&
      $this->routeMatch->getParameter('node')->id() == $items->getEntity()->id()
    ) {
      // Now we know we are on the node page and the node has a banner
      // paragraph of some sort. If the banner paragraph is the correct type,
      // we can prevent the original node title from displaying.
      /** @var \Drupal\paragraphs\ParagraphInterface $banner_paragraph */
      $banner_paragraph = $items->getEntity()
        ->get('su_page_banner')
        ->get(0)->entity;
      return AccessResult::forbiddenIf($banner_paragraph->bundle() == 'stanford_page_title_banner');
    }

    return AccessResult::neutral();
  }

  /**
   * Implements hook_node_access().
   */
  #[Hook('node_access')]
  public function nodeAccess(NodeInterface $node, $op, AccountInterface $account): AccessResult {
    if ($op == 'delete') {
      $site_config = $this->configFactory->get('system.site');
      $node_urls = [
        $node->toUrl()->toString(TRUE)->getGeneratedUrl(),
        "/node/{$node->id()}",
      ];

      // If the node is configured to be the home page, 404, or 403, prevent
      // the user from deleting. Unfortunately this only works for roles
      // without the "Bypass content access control" permission.
      if (array_intersect($node_urls, $site_config->get('page'))) {
        return AccessResult::forbidden();
      }
    }

    $locked_node_ids = $this->state->get('stanford_profile_helper.locked_admin_nodes', []);
    if (in_array($node->id(), $locked_node_ids)) {
      return $op === 'view' ? AccessResult::forbiddenIf($account->isAnonymous()) : AccessResult::forbidden();
    }
    return AccessResult::neutral();
  }

}
