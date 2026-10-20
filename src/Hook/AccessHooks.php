<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\RevisionableInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;

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
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(protected ConfigFactoryInterface $configFactory, protected RouteMatchInterface $routeMatch, protected StateInterface $state, protected EntityTypeManagerInterface $entityTypeManager) {}

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

  /**
   * Implements hook_ENTITY_TYPE_access().
   */
  #[Hook('paragraph_access')]
  public function paragraphAccess(ParagraphInterface $entity, $operation, AccountInterface $account) {
    // The "view" guard must stay first: the edit check below re-enters entity
    // access with the "update" operation, and this condition is what stops
    // that second pass from recursing.
    if ($operation == 'view' && $entity->bundle() == 'stanford_layout') {
      // Users who can edit the layout keep seeing it even when it holds
      // nothing, otherwise they'd have no way to add content to it.
      $editAccess = $entity->access('update', $account, TRUE);
      if ($editAccess->isAllowed()) {
        return AccessResult::neutral()->addCacheableDependency($editAccess);
      }

      $result = AccessResult::neutral()
        ->addCacheableDependency($editAccess)
        ->addCacheableDependency($entity);

      $parentField = $entity->get('parent_field_name')->getString();
      $parent = $this->getLayoutParent($entity, $parentField);
      // Without a parent there's no way to know what the layout holds.
      if (!$parent) {
        return $result;
      }
      $result->addCacheableDependency($parent);

      $layoutUuid = $entity->uuid();
      $children = iterator_to_array($parent->get($parentField)->getIterator());
      $children = array_filter(array_map(fn($item) => $item->entity, $children));
      // Publishing or moving any sibling can change the outcome.
      array_walk($children, fn(ParagraphInterface $child) => $result->addCacheableDependency($child));
      $children = array_filter($children, fn(ParagraphInterface $child) => $child->isPublished() && $child->getBehaviorSetting('layout_paragraphs', 'parent_uuid') == $layoutUuid);
      return AccessResult::forbiddenIf(!$children, 'No published children in the layout.')
        ->inheritCacheability($result);
    }
    return AccessResult::neutral();
  }

  /**
   * Get the parent revision that holds the given layout paragraph.
   *
   * The paragraph's parent entity is always the default revision, which isn't
   * the one being rendered on previews, revision pages or the latest version
   * of moderated content.
   *
   * @param \Drupal\paragraphs\ParagraphInterface $paragraph
   *   Layout paragraph.
   * @param string $parentField
   *   Field on the parent that references the paragraph.
   *
   * @return \Drupal\Core\Entity\FieldableEntityInterface|null
   *   The parent entity, or NULL if it can't be determined.
   */
  protected function getLayoutParent(ParagraphInterface $paragraph, string $parentField): ?FieldableEntityInterface {
    $parentType = $paragraph->get('parent_type')->getString();
    $parentId = $paragraph->get('parent_id')->getString();
    if (!$parentType || !$parentId || !$parentField) {
      return NULL;
    }

    // Previews, revision pages and latest version pages provide the parent
    // revision being rendered as a route parameter.
    foreach ($this->routeMatch->getParameters() as $parameter) {
      if (
        $parameter instanceof FieldableEntityInterface &&
        $parameter->getEntityTypeId() == $parentType &&
        $parameter->id() == $parentId &&
        $parameter->hasField($parentField)
      ) {
        return $parameter;
      }
    }

    $parent = $paragraph->getParentEntity();
    if (!$parent instanceof FieldableEntityInterface || !$parent->hasField($parentField)) {
      return NULL;
    }

    $revisionId = $paragraph->getRevisionId();
    if (!$revisionId || !$parent instanceof RevisionableInterface) {
      return $parent;
    }
    foreach ($parent->get($parentField) as $item) {
      if ($item->target_revision_id == $revisionId) {
        return $parent;
      }
    }

    // The default revision doesn't contain this paragraph revision, so find
    // the latest parent revision that does.
    $storage = $this->entityTypeManager->getStorage($parentType);
    $entityType = $storage->getEntityType();
    $parentRevisionIds = $storage->getQuery()
      ->accessCheck(FALSE)
      ->allRevisions()
      ->condition($entityType->getKey('id'), $parentId)
      ->condition("$parentField.target_revision_id", $revisionId)
      ->sort($entityType->getKey('revision'), 'DESC')
      ->range(0, 1)
      ->execute();

    /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
    $revision = $parentRevisionIds ? $storage->loadRevision(key($parentRevisionIds)) : NULL;
    return $revision instanceof FieldableEntityInterface ? $revision : $parent;
  }

}
