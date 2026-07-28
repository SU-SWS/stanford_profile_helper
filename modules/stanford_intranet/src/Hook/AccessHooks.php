<?php

declare(strict_types=1);

namespace Drupal\stanford_intranet\Hook;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
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

    $rids = $this->state->get('stanford_intranet.rids');
    $node_field_values = $node->get(EntityAccessFieldType::FIELD_NAME)
      ->getValue();

    // If the node has no access settings configured, we can say that it is
    // visible to all authenticated users.
    if (empty($node_field_values)) {
      $node_field_values = [['role' => 'authenticated', 'access' => ['view']]];
    }

    foreach ($node_field_values as $value) {
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
    $grants[] = [
      'realm' => 'stanford_intranet_author',
      'gid' => $node->getOwner()->id(),
      'grant_view' => 1,
      'grant_update' => 1,
      'grant_delete' => 1,
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
