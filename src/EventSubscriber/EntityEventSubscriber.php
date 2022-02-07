<?php

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\config_pages\ConfigPagesInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Config\StorageInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent;
use Drupal\core_event_dispatcher\Event\Entity\EntityAccessEvent;
use Drupal\core_event_dispatcher\Event\Entity\EntityFieldAccessEvent;
use Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent;
use Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\node\NodeInterface;
use Drupal\path_alias\AliasManagerInterface;
use Drupal\taxonomy\TermInterface;
use Drupal\user\RoleInterface;

/**
 * Class EntityEventSubscriber.
 *
 * Entity hooks event listeners, triggered from the core_event_dispatcher
 * module. This includes presave, update, delete, etc methods.
 */
class EntityEventSubscriber extends BaseEventSubscriber {

  /**
   * Core state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * Core database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Core route builder service.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected $routeBuilder;

  /**
   * Core config storage service.
   *
   * @var \Drupal\Core\Config\StorageInterface
   */
  protected $configStorage;

  /**
   * Core path alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * EntityEventSubscriber constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection service.
   * @param \Drupal\Core\Routing\RouteBuilderInterface $route_builder
   *   Route builder service.
   * @param \Drupal\Core\Config\StorageInterface $config_storage
   *   Config storage service.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   Path alias manager service.
   */
  public function __construct(StateInterface $state, Connection $database, RouteBuilderInterface $route_builder, StorageInterface $config_storage, AliasManagerInterface $alias_manager) {
    $this->state = $state;
    $this->database = $database;
    $this->routeBuilder = $route_builder;
    $this->configStorage = $config_storage;
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::ENTITY_PRE_SAVE => 'onEntityPreSave',
      HookEventDispatcherInterface::ENTITY_UPDATE => 'onEntityUpdate',
      HookEventDispatcherInterface::ENTITY_ACCESS => 'onEntityAccess',
      HookEventDispatcherInterface::ENTITY_FIELD_ACCESS => 'onFieldAccess',
    ];
  }

  /**
   * Entity pre-save event action, calls a method for each entity type.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityPresaveEvent $event
   *   Triggered event.
   *
   * @see hook_entity_presave().
   */
  public function onEntityPreSave(EntityPresaveEvent $event) {
    $method_name = $this->getMethodName('preSave', $event);
    if (method_exists($this, $method_name)) {
      $this->$method_name($event->getEntity(), $event->getOriginalEntity());
    }
  }

  /**
   * Entity update event action, calls a method for each entity type.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityUpdateEvent $event
   *   Triggered event.
   *
   * @see hook_entity_update()
   */
  public function onEntityUpdate(EntityUpdateEvent $event) {
    $method_name = $this->getMethodName('update', $event);
    if (method_exists($this, $method_name)) {
      $this->$method_name($event->getEntity(), $event->getOriginalEntity());
    }
  }

  /**
   * Entity access event action, calls a method for each entity type.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityAccessEvent $event
   *   Triggered event.
   *
   * @see hook_entity_access().
   */
  public function onEntityAccess(EntityAccessEvent $event) {
    $method_name = $this->getMethodName('access', $event);
    if (method_exists($this, $method_name)) {
      $event->addAccessResult($this->$method_name($event->getEntity(), $event->getOperation(), $event->getAccount()));
    }
  }

  /**
   * Entity field access event action.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityFieldAccessEvent $event
   *   Triggered event.
   *
   * @see hook_entity_field_access().
   */
  public function onFieldAccess(EntityFieldAccessEvent $event) {
    $field_definition = $event->getFieldDefinition();
    $items = $event->getItems();
    $account = $event->getAccount();

    if (
      $field_definition->getName() == 'status' &&
      $field_definition->getTargetEntityTypeId() == 'node' &&
      $items &&
      $items->getEntity()->id()
    ) {
      // Prevent unpublishing the home, 404 and 403 pages.
      $event->setAccessResult($this->accessNode($items->getEntity(), 'delete', $account));
      return;
    }

    if ($field_definition->getType() == 'entity_reference' && $field_definition->getSetting('handler') == 'layout_library') {
      $entity_type = $field_definition->getTargetEntityTypeId();
      $bundle = $field_definition->getTargetBundle();
      if (!$account->hasPermission("choose layout for $entity_type $bundle")) {
        $event->setAccessResult(AccessResult::forbidden());
        return;
      }
    }
    $event->setAccessResult(AccessResult::neutral());
  }

  /**
   * Get the Camel-Case method name for the event entity.
   *
   * @param string $prefix
   *   Method prefix before the entity type.
   * @param \Drupal\core_event_dispatcher\Event\Entity\AbstractEntityEvent $event
   *   Triggered event.
   *
   * @return string
   *   Camel Case method name.
   */
  protected function getMethodName($prefix, AbstractEntityEvent $event): string {
    $entity_type = $event->getEntity()->getEntityTypeId();
    $entity_type = str_replace(' ', '', ucwords(str_replace('_', ' ', $entity_type)));
    return "$prefix$entity_type";
  }

  /**
   * Check the access on the `node` entity.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   * @param string $op
   *   Operation
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Triggered event.
   *
   * @return \Drupal\Core\Access\AccessResultForbidden|\Drupal\Core\Access\AccessResultNeutral
   *   Access result object.
   *
   * @see hook_entity_access().
   */
  protected function accessNode(NodeInterface $node, $op, AccountInterface $account) {
    if ($op == 'delete') {
      $site_config = $this->configFactory()->get('system.site');
      $node_urls = [$node->toUrl()->toString(), "/node/{$node->id()}"];

      // If the node is configured to be the home page, 404, or 403, prevent the
      // user from deleting. Unfortunately this only works for roles without the
      // "Bypass content access control" permission.
      if (array_intersect($node_urls, $site_config->get('page'))) {
        return AccessResult::forbidden();
      }
    }
    return AccessResult::neutral();
  }

  /**
   * During `taxonomy_term` update, do some action.
   *
   * @param \Drupal\taxonomy\TermInterface $term
   *   The modified taxonomy term entity.
   * @param \Drupal\taxonomy\TermInterface $original_term
   *   The original taxonomy term entity before update.
   *
   * @see hook_entity_update().
   */
  protected function updateTaxonomyTerm(TermInterface $term, TermInterface $original_term) {
    // https://www.drupal.org/project/taxonomy_menu/issues/2867626
    $original_parent = $original_term->get('parent')->getString();
    if ($original_parent == $term->get('parent')->getString()) {
      return;
    }

    $menu_link_exists = $this->database->select('menu_tree', 'm')
      ->fields('m')
      ->condition('id', 'taxonomy_menu.menu_link%', 'LIKE')
      ->condition('route_param_key', 'taxonomy_term=' . $term->id())
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($menu_link_exists > 0) {
      $this->database->delete('menu_tree')
        ->condition('id', 'taxonomy_menu.menu_link%', 'LIKE')
        ->condition('route_param_key', 'taxonomy_term=' . $term->id())
        ->execute();
      $this->routeBuilder->rebuild();
    }
  }

  /**
   * On `node` save, perform some actions.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Node entity.
   *
   * @see hook_entity_presave().
   */
  protected function preSaveNode(NodeInterface $node) {
    // Invalidate any search result cached so the updated/new content will be
    // displayed for previously searched terms.
    Cache::invalidateTags(['config:views.view.search']);
  }

  /**
   * Pre `field_storage_config` entity save, perform some actions.
   *
   * @param \Drupal\field\FieldStorageConfigInterface $field_storage
   *   Field storage entity.
   *
   * @see hook_entity_presave().
   */
  protected function preSaveFieldStorageConfig(FieldStorageConfigInterface $field_storage) {
    // If a field is saved and the field permissions are public, lets just remove
    // those third party settings before save so that it keeps the config clean.
    if ($field_storage->getThirdPartySetting('field_permissions', 'permission_type') === 'public') {
      $field_storage->unsetThirdPartySetting('field_permissions', 'permission_type');
      $field_storage->calculateDependencies();
    }
  }

  /**
   * Pre-Save `config_pages`, perform some actions.
   *
   * @param \Drupal\config_pages\ConfigPagesInterface $config_page
   *   Config page entity.
   *
   * @see hook_node_presave().
   */
  protected function preSaveConfigPages(ConfigPagesInterface $config_page) {
    if ($config_page->hasField('su_site_url') && ($url_field = $config_page->get('su_site_url')
        ->getValue())) {
      // Set the xml sitemap module state to the new domain.
      $this->state->set('xmlsitemap_base_url', $url_field[0]['uri']);
    }

    // Invalidate cache tags on config pages save. This is a blanket cache clear
    // since config pages mostly affect the entire site.
    Cache::invalidateTags(['system.site', 'block_view', 'node_view']);
  }

  /**
   * Pre-save `menu_link_content` entity, perform some action.
   *
   * @param \Drupal\menu_link_content\MenuLinkContentInterface $menu_link
   *   Menu link entity.
   *
   * @see hook_entity_presave().
   */
  protected function preSaveMenuLinkContent(MenuLinkContentInterface $menu_link) {
    // For new menu link items created on a node form (normally), set the expanded
    // attribute so all menu items are expanded by default.
    if ($menu_link->isNew()) {
      $menu_link->set('expanded', TRUE);
    }

    // When a menu item is added as a child of another menu item clear the parent
    // pages cache so that the block shows up as it doesn't get invalidated just
    // by the menu cache tags.
    $parent_id = $menu_link->getParentId();
    if (!empty($parent_id)) {
      [$entity_name, $uuid] = explode(':', $parent_id);
      $menu_link_content = $this->entityTypeManager()->getStorage($entity_name)
        ->loadByProperties(['uuid' => $uuid]);

      if (is_array($menu_link_content)) {
        $parent_item = array_pop($menu_link_content);
        /** @var \Drupal\Core\Url $url */
        $url = $parent_item->getUrlObject();
        if (!$url->isExternal() && $url->isRouted()) {
          $params = $url->getRouteParameters();
          if (isset($params['node'])) {
            Cache::invalidateTags(['node:' . $params['node']]);
          }
        }
      }
    }
  }

  /**
   * On `user_role` pre-save, perform some actions.
   *
   * @param \Drupal\user\RoleInterface $role
   *   The role entity.
   *
   * @see hook_entity_presave().
   */
  protected function preSaveRole(RoleInterface $role) {
    // Only modify new roles if they are created through the UI and don't exist in
    // the config management - Prefix them with "custm_" so they can be easily
    // identifiable.

    if (
      PHP_SAPI != 'cli' &&
      $role->isNew() &&
      !in_array($role->getConfigDependencyName(), $this->configStorage->listAll())
    ) {
      $role->set('id', 'custm_' . $role->id());
    }
  }

  /**
   * Pre-save `redirect`, perform some action.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $redirect
   *   Redirect entity.
   *
   * @see hook_entity_presave().
   */
  protected function preSaveRedirect(ContentEntityInterface $redirect) {
    $destination = $redirect->get('redirect_redirect')->getString();

    // If a redirect is added to go to the aliased path of a node (often from
    // importing redirect), change the destination to target the node instead.
    // This works if the destination is `/about` or `/node/9`.
    if (preg_match('/^internal:(\/.*)/', $destination, $matches)) {
      // Find the internal path from the alias.
      $path = $this->aliasManager->getPathByAlias($matches[1]);

      // Grab the node id from the internal path and use that as the destination.
      if (preg_match('/node\/(\d+)/', $path, $matches)) {
        $redirect->set('redirect_redirect', 'entity:node/' . $matches[1]);
      }
    }

    // Purge everything for the source url so that it can redirect without any
    // intervention.
    if ($this->moduleHandler()->moduleExists('purge_processor_lateruntime')) {
      $source = $redirect->get('redirect_source')->getString();
      self::purgePath($source);
    }
  }

  /**
   * Purge the given path using purge processors.
   *
   * @param string $path
   *   Relative path to the drupal application.
   */
  protected static function purgePath($path) {
    $url = Url::fromUserInput('/' . trim($path, '/'), ['absolute' => TRUE])
      ->toString();

    $purgeInvalidationFactory = \Drupal::service('purge.invalidation.factory');
    $purgeProcessors = \Drupal::service('purge.processors');
    $purgePurgers = \Drupal::service('purge.purgers');

    $processor = $purgeProcessors->get('lateruntime');
    $invalidations = [$purgeInvalidationFactory->get('url', $url)];

    try {
      $purgePurgers->invalidate($processor, $invalidations);
    }
    catch (\Exception $e) {
      \Drupal::logger('stanford_profile_helper')->error($e->getMessage());
    }
  }

}
