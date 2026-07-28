<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_intranet\Unit\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Config\ImmutableConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\State\StateInterface;
use Drupal\block\BlockInterface;
use Drupal\node\NodeInterface;
use Drupal\paragraphs\ParagraphInterface;
use Drupal\stanford_intranet\Hook\AccessHooks;
use Drupal\stanford_intranet\Plugin\Field\FieldType\EntityAccessFieldType;
use Drupal\Tests\UnitTestCase;
use Drupal\user\RoleInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for AccessHooks.
 */
#[Group('stanford_intranet')]
#[CoversClass(AccessHooks::class)]
class AccessHooksTest extends UnitTestCase {

  /**
   * Mocked state service.
   *
   * @var \Drupal\Core\State\StateInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected StateInterface $state;

  /**
   * Mocked config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected ConfigFactoryInterface $configFactory;

  /**
   * Mocked entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_intranet\Hook\AccessHooks
   */
  protected AccessHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->state = $this->createMock(StateInterface::class);
    $this->configFactory = $this->createMock(ConfigFactoryInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->hooks = new AccessHooks($this->state, $this->configFactory, $this->entityTypeManager);
  }

  /**
   * Builds a state map callback for state->get() using a map of key=>value.
   */
  protected function stateGetCallback(array $map): \Closure {
    return function (string $key, $default = NULL) use ($map) {
      return $map[$key] ?? $default;
    };
  }

  /**
   * File uploads should be forbidden when all conditions match.
   */
  public function testEntityCreateAccessForbidden() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn(2);

    $this->state->method('get')
      ->willReturnCallback($this->stateGetCallback([
        'stanford_intranet' => TRUE,
        'stanford_intranet.allow_file_uploads' => FALSE,
      ]));

    $context = ['entity_type_id' => 'media'];
    $result = $this->hooks->entityCreateAccess($account, $context, 'file');
    $this->assertTrue($result->isForbidden());
  }

  /**
   * User 1 is never blocked from uploading files.
   */
  public function testEntityCreateAccessNeutralForUserOne() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn(1);

    $this->state->method('get')
      ->willReturnCallback($this->stateGetCallback([
        'stanford_intranet' => TRUE,
        'stanford_intranet.allow_file_uploads' => FALSE,
      ]));

    $context = ['entity_type_id' => 'media'];
    $result = $this->hooks->entityCreateAccess($account, $context, 'file');
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Non-media entity types are unaffected.
   */
  public function testEntityCreateAccessNeutralForNonMediaEntityType() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn(2);

    $this->state->method('get')
      ->willReturnCallback($this->stateGetCallback([
        'stanford_intranet' => TRUE,
        'stanford_intranet.allow_file_uploads' => FALSE,
      ]));

    $context = ['entity_type_id' => 'node'];
    $result = $this->hooks->entityCreateAccess($account, $context, 'file');
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Non-file bundles on media are unaffected.
   */
  public function testEntityCreateAccessNeutralForNonFileBundle() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn(2);

    $this->state->method('get')
      ->willReturnCallback($this->stateGetCallback([
        'stanford_intranet' => TRUE,
        'stanford_intranet.allow_file_uploads' => FALSE,
      ]));

    $context = ['entity_type_id' => 'media'];
    $result = $this->hooks->entityCreateAccess($account, $context, 'image');
    $this->assertTrue($result->isNeutral());
  }

  /**
   * When the intranet is disabled, uploads are unaffected.
   */
  public function testEntityCreateAccessNeutralWhenIntranetDisabled() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn(2);

    $this->state->method('get')
      ->willReturnCallback($this->stateGetCallback([
        'stanford_intranet' => FALSE,
        'stanford_intranet.allow_file_uploads' => FALSE,
      ]));

    $context = ['entity_type_id' => 'media'];
    $result = $this->hooks->entityCreateAccess($account, $context, 'file');
    $this->assertTrue($result->isNeutral());
  }

  /**
   * When file uploads are explicitly allowed, uploads are unaffected.
   */
  public function testEntityCreateAccessNeutralWhenUploadsAllowed() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn(2);

    $this->state->method('get')
      ->willReturnCallback($this->stateGetCallback([
        'stanford_intranet' => TRUE,
        'stanford_intranet.allow_file_uploads' => TRUE,
      ]));

    $context = ['entity_type_id' => 'media'];
    $result = $this->hooks->entityCreateAccess($account, $context, 'file');
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Authenticated users are never blocked by entityAccess().
   */
  public function testEntityAccessNeutralForAuthenticatedRole() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn([RoleInterface::AUTHENTICATED_ID]);

    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->never())->method('getEntityTypeId');

    $result = $this->hooks->entityAccess($entity, 'view', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * When the intranet is disabled, access is unaffected.
   */
  public function testEntityAccessNeutralWhenIntranetDisabled() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn([]);
    $this->state->method('get')->willReturn(FALSE);

    $entity = $this->createMock(EntityInterface::class);
    $entity->expects($this->never())->method('getEntityTypeId');

    $result = $this->hooks->entityAccess($entity, 'view', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Paragraph entities inherit access from their parents and are skipped.
   */
  public function testEntityAccessNeutralForParagraph() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn([]);
    $this->state->method('get')->willReturn(TRUE);

    $entity = $this->createMock(ParagraphInterface::class);
    $entity->expects($this->never())->method('getEntityTypeId');

    $result = $this->hooks->entityAccess($entity, 'view', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Blocks in the default public block list are allowed by plugin ID.
   */
  public function testEntityAccessBlockAllowedByDefaultPluginId() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn([]);
    $this->state->method('get')->willReturn(TRUE);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('public_blocks')->willReturn(NULL);
    $this->configFactory->method('get')
      ->with('stanford_intranet.settings')
      ->willReturn($config);

    $entity = $this->createMock(BlockInterface::class);
    $entity->method('getEntityTypeId')->willReturn('block');
    $entity->method('getPluginId')->willReturn('system_main_block');
    $entity->method('id')->willReturn('mainblock');

    $result = $this->hooks->entityAccess($entity, 'view', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Blocks in the default public block list are allowed by entity ID.
   */
  public function testEntityAccessBlockAllowedByDefaultEntityId() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn([]);
    $this->state->method('get')->willReturn(TRUE);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('public_blocks')->willReturn(NULL);
    $this->configFactory->method('get')
      ->with('stanford_intranet.settings')
      ->willReturn($config);

    $entity = $this->createMock(BlockInterface::class);
    $entity->method('getEntityTypeId')->willReturn('block');
    $entity->method('getPluginId')->willReturn('some_custom_plugin');
    $entity->method('id')->willReturn('help');

    $result = $this->hooks->entityAccess($entity, 'view', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Blocks not in the allowed list are forbidden.
   */
  public function testEntityAccessBlockForbiddenNotInList() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn([]);
    $this->state->method('get')->willReturn(TRUE);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('public_blocks')->willReturn(NULL);
    $this->configFactory->method('get')
      ->with('stanford_intranet.settings')
      ->willReturn($config);

    $entity = $this->createMock(BlockInterface::class);
    $entity->method('getEntityTypeId')->willReturn('block');
    $entity->method('getPluginId')->willReturn('some_custom_plugin');
    $entity->method('id')->willReturn('some_block_id');

    $result = $this->hooks->entityAccess($entity, 'view', $account);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * A configured public block list overrides the default list.
   */
  public function testEntityAccessBlockAllowedByConfiguredList() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn([]);
    $this->state->method('get')->willReturn(TRUE);

    $config = $this->createMock(ImmutableConfig::class);
    $config->method('get')->with('public_blocks')->willReturn(['custom_block']);
    $this->configFactory->method('get')
      ->with('stanford_intranet.settings')
      ->willReturn($config);

    $entity = $this->createMock(BlockInterface::class);
    $entity->method('getEntityTypeId')->willReturn('block');
    $entity->method('getPluginId')->willReturn('custom_block');
    $entity->method('id')->willReturn('custom_block_instance');

    $result = $this->hooks->entityAccess($entity, 'view', $account);
    $this->assertTrue($result->isNeutral());
  }

  /**
   * Non-block entities that fall through are forbidden.
   */
  public function testEntityAccessForbiddenForNonBlockEntity() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('getRoles')->willReturn([]);
    $this->state->method('get')->willReturn(TRUE);

    $entity = $this->createMock(EntityInterface::class);
    $entity->method('getEntityTypeId')->willReturn('node');

    $result = $this->hooks->entityAccess($entity, 'view', $account);
    $this->assertTrue($result->isForbidden());
  }

  /**
   * Unpublished nodes never get access record adjustments.
   */
  public function testNodeAccessRecordsUnpublished() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('isPublished')->willReturn(FALSE);

    $grants = $this->hooks->nodeAccessRecords($node);
    $this->assertSame([], $grants);
  }

  /**
   * When the intranet is disabled, no access records are added.
   */
  public function testNodeAccessRecordsIntranetDisabled() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('isPublished')->willReturn(TRUE);
    $this->state->method('get')->willReturn(FALSE);

    $grants = $this->hooks->nodeAccessRecords($node);
    $this->assertSame([], $grants);
  }

  /**
   * Nodes without the access field are unaffected.
   */
  public function testNodeAccessRecordsNoField() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('isPublished')->willReturn(TRUE);
    $this->state->method('get')->willReturn(TRUE);
    $node->method('hasField')->with(EntityAccessFieldType::FIELD_NAME)->willReturn(FALSE);

    $grants = $this->hooks->nodeAccessRecords($node);
    $this->assertSame([], $grants);
  }

  /**
   * Nodes with no field values default to authenticated-view access.
   */
  public function testNodeAccessRecordsEmptyFieldValuesDefaultToAuthenticated() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('isPublished')->willReturn(TRUE);
    $node->method('hasField')->with(EntityAccessFieldType::FIELD_NAME)->willReturn(TRUE);

    $this->state->method('get')
      ->willReturnCallback($this->stateGetCallback([
        'stanford_intranet' => TRUE,
        'stanford_intranet.rids' => ['authenticated' => 111],
      ]));

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('getValue')->willReturn([]);
    $node->method('get')->with(EntityAccessFieldType::FIELD_NAME)->willReturn($field_list);

    $owner = $this->createMock(AccountInterface::class);
    $owner->method('id')->willReturn(42);
    $node->method('getOwner')->willReturn($owner);

    $grants = $this->hooks->nodeAccessRecords($node);

    $this->assertCount(2, $grants);
    $this->assertSame('stanford_intranet_roles', $grants[0]['realm']);
    $this->assertSame(111, $grants[0]['gid']);
    $this->assertSame(1, $grants[0]['grant_view']);
    $this->assertSame(0, $grants[0]['grant_update']);
    $this->assertSame(0, $grants[0]['grant_delete']);
    $this->assertSame('stanford_intranet_author', $grants[1]['realm']);
    $this->assertSame(42, $grants[1]['gid']);
    $this->assertSame(1, $grants[1]['grant_view']);
    $this->assertSame(1, $grants[1]['grant_update']);
    $this->assertSame(1, $grants[1]['grant_delete']);
  }

  /**
   * Nodes with configured field values produce matching access grants.
   */
  public function testNodeAccessRecordsWithFieldValues() {
    $node = $this->createMock(NodeInterface::class);
    $node->method('isPublished')->willReturn(TRUE);
    $node->method('hasField')->with(EntityAccessFieldType::FIELD_NAME)->willReturn(TRUE);

    $this->state->method('get')
      ->willReturnCallback($this->stateGetCallback([
        'stanford_intranet' => TRUE,
        'stanford_intranet.rids' => ['student' => 222, 'staff' => 333],
      ]));

    $field_list = $this->createMock(FieldItemListInterface::class);
    $field_list->method('getValue')->willReturn([
      ['role' => 'student', 'access' => ['view']],
      ['role' => 'staff', 'access' => ['view', 'update']],
    ]);
    $node->method('get')->with(EntityAccessFieldType::FIELD_NAME)->willReturn($field_list);

    $owner = $this->createMock(AccountInterface::class);
    $owner->method('id')->willReturn(7);
    $node->method('getOwner')->willReturn($owner);

    $grants = $this->hooks->nodeAccessRecords($node);

    $this->assertCount(3, $grants);
    $this->assertSame(222, $grants[0]['gid']);
    $this->assertSame(1, $grants[0]['grant_view']);
    $this->assertSame(0, $grants[0]['grant_update']);
    $this->assertSame(333, $grants[1]['gid']);
    $this->assertSame(1, $grants[1]['grant_view']);
    $this->assertSame(1, $grants[1]['grant_update']);
    $this->assertSame(0, $grants[1]['grant_delete']);
    $this->assertSame('stanford_intranet_author', $grants[2]['realm']);
    $this->assertSame(7, $grants[2]['gid']);
  }

  /**
   * Node grants should map the account's roles to configured group IDs.
   */
  public function testNodeGrantsMapsRolesToGids() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn(9);
    $account->method('getRoles')->willReturn(['student', 'unmapped_role']);

    $this->state->method('get')
      ->with('stanford_intranet.rids', [])
      ->willReturn(['student' => 222, 'staff' => 333]);

    $grants = $this->hooks->nodeGrants($account, 'view');

    $this->assertSame([9], $grants['stanford_intranet_author']);
    $this->assertSame([222], $grants['stanford_intranet_roles']);
  }

  /**
   * When there are no configured role IDs, the roles grant is empty.
   */
  public function testNodeGrantsEmptyRids() {
    $account = $this->createMock(AccountInterface::class);
    $account->method('id')->willReturn(9);
    $account->method('getRoles')->willReturn(['authenticated']);

    $this->state->method('get')
      ->with('stanford_intranet.rids', [])
      ->willReturn([]);

    $grants = $this->hooks->nodeGrants($account, 'view');

    $this->assertSame([9], $grants['stanford_intranet_author']);
    $this->assertSame([], $grants['stanford_intranet_roles']);
  }

  /**
   * Inserting a new role should add it to the tracked role ID state.
   */
  public function testUserRoleInsertAddsNewRole() {
    $role = $this->createMock(RoleInterface::class);

    $this->state->method('get')
      ->with('stanford_intranet.rids', [])
      ->willReturn(['authenticated' => 1]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([
      'authenticated' => $this->createMock(RoleInterface::class),
      'student' => $this->createMock(RoleInterface::class),
    ]);
    $this->entityTypeManager->method('getStorage')
      ->with('user_role')
      ->willReturn($storage);

    $this->state->expects($this->once())
      ->method('set')
      ->with('stanford_intranet.rids', $this->callback(function ($value) {
        return isset($value['authenticated'], $value['student']);
      }));

    $this->hooks->userRoleInsert($role);
  }

  /**
   * If all roles are already tracked, the state is still (re)set unchanged.
   */
  public function testUserRoleInsertNoNewRoles() {
    $role = $this->createMock(RoleInterface::class);

    $this->state->method('get')
      ->with('stanford_intranet.rids', [])
      ->willReturn(['authenticated' => 1]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')->willReturn([
      'authenticated' => $this->createMock(RoleInterface::class),
    ]);
    $this->entityTypeManager->method('getStorage')
      ->with('user_role')
      ->willReturn($storage);

    $this->state->expects($this->once())
      ->method('set')
      ->with('stanford_intranet.rids', ['authenticated' => 1]);

    $this->hooks->userRoleInsert($role);
  }

  /**
   * Deleting a role should remove it from the tracked role ID state.
   */
  public function testUserRolePredeleteRemovesRole() {
    $role = $this->createMock(RoleInterface::class);
    $role->method('id')->willReturn('student');

    $this->state->method('get')
      ->with('stanford_intranet.rids', [])
      ->willReturn(['authenticated' => 1, 'student' => 2]);

    $this->state->expects($this->once())
      ->method('set')
      ->with('stanford_intranet.rids', ['authenticated' => 1]);

    $this->hooks->userRolePredelete($role);
  }

}
