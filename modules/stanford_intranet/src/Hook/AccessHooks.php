<?php

declare(strict_types=1);

namespace Drupal\stanford_intranet\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\UserSession;
use Drupal\Core\State\StateInterface;
use Drupal\menu_link_content\MenuLinkContentInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_intranet\Plugin\Field\FieldType\EntityAccessFieldType;
use Drupal\user\RoleInterface;

/**
 * Hooks that control access to entities and nodes when the intranet is on.
 */
class AccessHooks {

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(
    protected StateInterface $state,
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {}

  /**
   * Implements hook_entity_create_access().
   */
  #[Hook('entity_create_access')]
  public function entityCreateAccess(AccountInterface $account, array $context, $entity_bundle) {
    // Block access to uploading files on the intranet. Leave the door open for
    // the user 1 account though.
    if (
      $context['entity_type_id'] == 'media' &&
      $entity_bundle == 'file' &&
      $account->id() != 1 &&
      $this->state->get('stanford_intranet', FALSE) &&
      !$this->state->get('stanford_intranet.allow_file_uploads', FALSE)
    ) {
      return AccessResult::forbidden();
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_entity_access().
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    // Paragraphs inherit their access from the parents they live on, so we can
    // ignore them.
    // Check for the role because doing `$account->isAuthenticated() only checks
    // for the uid > 0. This doesn't work for search_api functionality, so just
    // check for the role instead.
    if (
      !in_array(RoleInterface::AUTHENTICATED_ID, $account->getRoles()) &&
      $this->state->get('stanford_intranet', FALSE) &&
      !($entity instanceof ParagraphInterface)
    ) {
      if ($entity->getEntityTypeId() == 'block') {
        $default = ['system_main_block', 'help', 'system_messages_block'];
        $allowed_blocks = $this->configFactory->get('stanford_intranet.settings')
          ->get('public_blocks') ?: $default;

        if (
          in_array($entity->getPluginId(), $allowed_blocks) ||
          in_array($entity->id(), $allowed_blocks)
        ) {
          return AccessResult::neutral();
        }
      }
      // Prevent all access to non-authenticated users.
      return AccessResult::forbidden();
    }
    return AccessResult::neutral();
  }

  /**
   * Implements hook_xmlsitemap_link_alter().
   */
  #[Hook('xmlsitemap_link_alter')]
  public function xmlsitemapLinkAlter(array &$link, array $context): void {
    if (!$this->state->get('stanford_intranet', FALSE)) {
      return;
    }

    $entity = $context['entity'] ?? NULL;
    if (!$entity instanceof FieldableEntityInterface || trim((string) ($link['loc'] ?? ''), '/') === '') {
      return;
    }

    // Entities with role specific access are kept out of the sitemap so that
    // the restricted urls aren't disclosed to every authenticated user.
    if (
      $entity->hasField(EntityAccessFieldType::FIELD_NAME) &&
      $entity->get(EntityAccessFieldType::FIELD_NAME)->count()
    ) {
      return;
    }

    // XmlSitemap decides if a link belongs in the sitemap by checking access as
    // an anonymous user, which is always forbidden above when the intranet is
    // enabled. Check the access again as a generic authenticated user so the
    // pages any logged-in user can see are able to generate in the sitemap.
    // Unpublished entities are still denied since they have no grants.
    //
    // The uid matters for two reasons: entity access results are statically
    // cached per uid and xmlsitemap just checked this entity as the anonymous
    // user, so uid 0 would return that cached "forbidden" result instead of
    // re-evaluating. A negative uid also can't match the author grant of an
    // entity owned by the anonymous user.
    $account = new UserSession([
      'uid' => -1,
      'roles' => [RoleInterface::AUTHENTICATED_ID],
    ]);

    try {
      // Menu links are their own entity but they point somewhere else, so
      // check the access of the linked route instead of the menu link itself.
      $link['access'] = $entity instanceof MenuLinkContentInterface
        ? $entity->getUrlObject()->access($account)
        : $entity->access('view', $account);
    }
    catch (\Exception $e) {
      // Leave the link as xmlsitemap built it if the access can't be checked.
    }
  }

  /**
   * Implements hook_node_access_records().
   */
  #[Hook('node_access_records')]
  public function nodeAccessRecords(NodeInterface $node) {
    $grants = [];

    // If intranet is disabled or the node is not published, we don't want to
    // adjust any access.
    if (
      !$node->isPublished() ||
      !$this->state->get('stanford_intranet', FALSE) ||
      !$node->hasField(EntityAccessFieldType::FIELD_NAME)
    ) {
      return $grants;
    }

    $rids = $this->state->get('stanford_intranet.rids', []);
    $node_field_values = $node->get(EntityAccessFieldType::FIELD_NAME)
      ->getValue();

    // If the node has no access settings configured, we can say that it is
    // visible to all authenticated users.
    if (empty($node_field_values)) {
      $node_field_values = [['role' => 'authenticated', 'access' => ['view']]];
    }

    foreach ($node_field_values as $value) {
      // A role that was deleted, or never registered, has no grant id.
      if (!isset($rids[$value['role']])) {
        continue;
      }
      $grant = [
        'realm' => 'stanford_intranet_roles',
        'gid' => $rids[$value['role']],
        'grant_view' => 0,
        'grant_update' => 0,
        'grant_delete' => 0,
      ];

      foreach ($value['access'] as $access) {
        $grant["grant_$access"] = 1;
      }
      $grants[] = $grant;
    }
    // Authors keep view access to their own content so that the intranet role
    // restrictions never hide a node from the person who wrote it. Update and
    // delete are deliberately left off: grants are keyed on the uid and are
    // only recalculated when the node is saved, so an author grant that allowed
    // editing would outlive any role change. A user demoted to a role with no
    // editing permissions would keep an Edit tab on everything they had
    // previously authored. Leaving these at 0 hands the decision back to the
    // node permissions of whatever roles the user currently holds.
    $grants[] = [
      'realm' => 'stanford_intranet_author',
      'gid' => $node->getOwner()->id(),
      'grant_view' => 1,
      'grant_update' => 0,
      'grant_delete' => 0,
    ];

    return $grants;
  }

  /**
   * Implements hook_node_grants().
   */
  #[Hook('node_grants')]
  public function nodeGrants(AccountInterface $account, $op) {
    $rids = $this->state->get('stanford_intranet.rids', []);

    $gids = [];
    $roles = $account->getRoles();
    foreach ($roles as $role_name) {
      if (isset($rids[$role_name])) {
        $gids[] = $rids[$role_name];
      }
    }

    return [
      'stanford_intranet_author' => [$account->id()],
      'stanford_intranet_roles' => $gids,
    ];
  }

  /**
   * Implements hook_ENTITY_TYPE_insert().
   */
  #[Hook('user_role_insert')]
  public function userRoleInsert(RoleInterface $role) {
    $state = $this->state->get('stanford_intranet.rids', []);
    $state = array_flip($state);
    $roles = $this->entityTypeManager
      ->getStorage('user_role')
      ->loadMultiple();

    foreach (array_keys($roles) as $role_id) {
      if (!in_array($role_id, $state)) {
        $state[] = $role_id;
      }
    }

    $this->state->set('stanford_intranet.rids', array_flip($state));
  }

  /**
   * Implements hook_ENTITY_TYPE_predelete().
   */
  #[Hook('user_role_predelete')]
  public function userRolePredelete(RoleInterface $role) {
    $state = $this->state->get('stanford_intranet.rids', []);
    unset($state[$role->id()]);
    $this->state->set('stanford_intranet.rids', $state);
  }

}
