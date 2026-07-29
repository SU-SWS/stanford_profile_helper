<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_policy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_policy\Entity\SuPolicyLog;
use Drupal\stanford_policy\SuPolicyLogAccessControlHandler;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Kernel tests for SuPolicyLogAccessControlHandler.
 */
#[CoversClass(SuPolicyLogAccessControlHandler::class)]
#[Group('stanford_policy')]
#[RunTestsInSeparateProcesses]
class SuPolicyLogAccessControlHandlerTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'field',
    'text',
    'node',
    'book',
    'config_pages',
    'stanford_policy',
  ];

  /**
   * Test policy log entity.
   *
   * @var \Drupal\stanford_policy\SuPolicyLogInterface
   */
  protected $policyLog;

  /**
   * User with view permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $viewUser;

  /**
   * User with edit permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $editUser;

  /**
   * User with delete permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $deleteUser;

  /**
   * User with create permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $createUser;

  /**
   * User with administer permission.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $adminUser;

  /**
   * User with no permissions.
   *
   * @var \Drupal\user\UserInterface
   */
  protected $noPermUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('su_policy_log');
    $this->installSchema('system', ['sequences']);
    $this->installSchema('book', ['book']);
    $this->installConfig(['stanford_policy']);

    // Create test roles and users.
    $this->createUsersWithPermissions();

    // Create a regular user to be the owner.
    $owner = User::create(['name' => 'owner_user']);
    $owner->save();

    // Create a test policy log entity.
    $this->policyLog = SuPolicyLog::create([
      'uid' => $owner->id(),
    ]);
    $this->policyLog->save();
  }

  /**
   * Create users with different permission sets.
   */
  protected function createUsersWithPermissions(): void {
    // User with view permission only.
    $role = Role::create(['id' => 'view_role', 'label' => 'View Role']);
    $role->grantPermission('view policy log');
    $role->save();
    $this->viewUser = User::create([
      'name' => 'view_user',
      'roles' => ['view_role'],
    ]);
    $this->viewUser->save();

    // User with edit permission only.
    $role = Role::create(['id' => 'edit_role', 'label' => 'Edit Role']);
    $role->grantPermission('edit policy log');
    $role->save();
    $this->editUser = User::create([
      'name' => 'edit_user',
      'roles' => ['edit_role'],
    ]);
    $this->editUser->save();

    // User with delete permission only.
    $role = Role::create(['id' => 'delete_role', 'label' => 'Delete Role']);
    $role->grantPermission('delete policy log');
    $role->save();
    $this->deleteUser = User::create([
      'name' => 'delete_user',
      'roles' => ['delete_role'],
    ]);
    $this->deleteUser->save();

    // User with create permission only.
    $role = Role::create(['id' => 'create_role', 'label' => 'Create Role']);
    $role->grantPermission('create policy log');
    $role->save();
    $this->createUser = User::create([
      'name' => 'create_user',
      'roles' => ['create_role'],
    ]);
    $this->createUser->save();

    // User with administer permission.
    $role = Role::create(['id' => 'admin_role', 'label' => 'Admin Role']);
    $role->grantPermission('administer policy log');
    $role->save();
    $this->adminUser = User::create([
      'name' => 'admin_user',
      'roles' => ['admin_role'],
    ]);
    $this->adminUser->save();

    // User with no permissions.
    $this->noPermUser = User::create(['name' => 'no_perm_user']);
    $this->noPermUser->save();
  }

  /**
   * Test view access.
   *
   * // Covers: checkAccess
   */
  public function testViewAccess(): void {
    // User with view permission should have access.
    $access = $this->policyLog->access('view', $this->viewUser, TRUE);
    $this->assertTrue($access->isAllowed(), 'User with view permission can view policy log');

    // User without view permission should not have access.
    $access = $this->policyLog->access('view', $this->noPermUser, TRUE);
    $this->assertFalse($access->isAllowed(), 'User without view permission cannot view policy log');

    // Admin user without view permission should not have view access.
    // (administer permission only applies to update/delete/create, not view)
    $access = $this->policyLog->access('view', $this->adminUser, TRUE);
    $this->assertFalse($access->isAllowed(), 'Admin user without view permission cannot view policy log');
  }

  /**
   * Test update access.
   *
   * // Covers: checkAccess
   */
  public function testUpdateAccess(): void {
    // User with edit permission should have access.
    $access = $this->policyLog->access('update', $this->editUser, TRUE);
    $this->assertTrue($access->isAllowed(), 'User with edit permission can update policy log');

    // User with administer permission should have access.
    $access = $this->policyLog->access('update', $this->adminUser, TRUE);
    $this->assertTrue($access->isAllowed(), 'Admin user can update policy log');

    // User without permissions should not have allowed access.
    $access = $this->policyLog->access('update', $this->noPermUser, TRUE);
    $this->assertFalse($access->isAllowed(), 'User without permissions cannot update policy log');
  }

  /**
   * Test delete access.
   *
   * // Covers: checkAccess
   */
  public function testDeleteAccess(): void {
    // User with delete permission should have access.
    $access = $this->policyLog->access('delete', $this->deleteUser, TRUE);
    $this->assertTrue($access->isAllowed(), 'User with delete permission can delete policy log');

    // User with administer permission should have access.
    $access = $this->policyLog->access('delete', $this->adminUser, TRUE);
    $this->assertTrue($access->isAllowed(), 'Admin user can delete policy log');

    // User without permissions should not have allowed access.
    $access = $this->policyLog->access('delete', $this->noPermUser, TRUE);
    $this->assertFalse($access->isAllowed(), 'User without permissions cannot delete policy log');
  }

  /**
   * Test create access.
   *
   * // Covers: checkCreateAccess
   */
  public function testCreateAccess(): void {
    $access_control_handler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('su_policy_log');

    // User with create permission should have access.
    $access = $access_control_handler->createAccess(NULL, $this->createUser, [], TRUE);
    $this->assertTrue($access->isAllowed(), 'User with create permission can create policy log');

    // User with administer permission should have access.
    $access = $access_control_handler->createAccess(NULL, $this->adminUser, [], TRUE);
    $this->assertTrue($access->isAllowed(), 'Admin user can create policy log');

    // User without permissions should not have allowed access.
    $access = $access_control_handler->createAccess(NULL, $this->noPermUser, [], TRUE);
    $this->assertFalse($access->isAllowed(), 'User without permissions cannot create policy log');
  }

  /**
   * Test unknown operation returns neutral.
   *
   * // Covers: checkAccess
   */
  public function testUnknownOperationReturnsNeutral(): void {
    $access = $this->policyLog->access('unknown_operation', $this->adminUser, TRUE);
    $this->assertTrue($access->isNeutral(), 'Unknown operation returns neutral access');

    $access = $this->policyLog->access('custom_op', $this->viewUser, TRUE);
    $this->assertTrue($access->isNeutral(), 'Custom operation returns neutral access');
  }

  /**
   * Test that OR logic works for update permission.
   *
   * // Covers: checkAccess
   */
  public function testUpdateOrPermission(): void {
    // User with edit permission (no administer) should have access.
    $access = $this->policyLog->access('update', $this->editUser, TRUE);
    $this->assertTrue($access->isAllowed(), 'User with edit permission has update access');

    // User with administer permission (no edit) should have access.
    $role = Role::create(['id' => 'admin_only_role', 'label' => 'Admin Only Role']);
    $role->grantPermission('administer policy log');
    $role->save();
    $adminOnlyUser = User::create([
      'name' => 'admin_only_user',
      'roles' => ['admin_only_role'],
    ]);
    $adminOnlyUser->save();

    $access = $this->policyLog->access('update', $adminOnlyUser, TRUE);
    $this->assertTrue($access->isAllowed(), 'User with administer permission has update access');
  }

  /**
   * Test that OR logic works for delete permission.
   *
   * // Covers: checkAccess
   */
  public function testDeleteOrPermission(): void {
    // User with delete permission (no administer) should have access.
    $access = $this->policyLog->access('delete', $this->deleteUser, TRUE);
    $this->assertTrue($access->isAllowed(), 'User with delete permission has delete access');

    // User with administer permission (no delete) should have access.
    $role = Role::create(['id' => 'admin_only_role2', 'label' => 'Admin Only Role 2']);
    $role->grantPermission('administer policy log');
    $role->save();
    $adminOnlyUser = User::create([
      'name' => 'admin_only_user2',
      'roles' => ['admin_only_role2'],
    ]);
    $adminOnlyUser->save();

    $access = $this->policyLog->access('delete', $adminOnlyUser, TRUE);
    $this->assertTrue($access->isAllowed(), 'User with administer permission has delete access');
  }

  /**
   * Test that OR logic works for create permission.
   *
   * // Covers: checkCreateAccess
   */
  public function testCreateOrPermission(): void {
    $access_control_handler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('su_policy_log');

    // User with create permission (no administer) should have access.
    $access = $access_control_handler->createAccess(NULL, $this->createUser, [], TRUE);
    $this->assertTrue($access->isAllowed(), 'User with create permission has create access');

    // User with administer permission (no create) should have access.
    $role = Role::create(['id' => 'admin_only_role3', 'label' => 'Admin Only Role 3']);
    $role->grantPermission('administer policy log');
    $role->save();
    $adminOnlyUser = User::create([
      'name' => 'admin_only_user3',
      'roles' => ['admin_only_role3'],
    ]);
    $adminOnlyUser->save();

    $access = $access_control_handler->createAccess(NULL, $adminOnlyUser, [], TRUE);
    $this->assertTrue($access->isAllowed(), 'User with administer permission has create access');
  }

  /**
   * Test user with multiple permissions.
   *
   * // Covers: checkAccess
   * // Covers: checkCreateAccess
   */
  public function testUserWithMultiplePermissions(): void {
    // Create user with multiple permissions.
    $role = Role::create(['id' => 'multi_perm_role', 'label' => 'Multi Permission Role']);
    $role->grantPermission('view policy log');
    $role->grantPermission('edit policy log');
    $role->grantPermission('delete policy log');
    $role->grantPermission('create policy log');
    $role->save();

    $multiPermUser = User::create([
      'name' => 'multi_perm_user',
      'roles' => ['multi_perm_role'],
    ]);
    $multiPermUser->save();

    // Test all operations.
    $access = $this->policyLog->access('view', $multiPermUser, TRUE);
    $this->assertTrue($access->isAllowed(), 'User with multiple permissions can view');

    $access = $this->policyLog->access('update', $multiPermUser, TRUE);
    $this->assertTrue($access->isAllowed(), 'User with multiple permissions can update');

    $access = $this->policyLog->access('delete', $multiPermUser, TRUE);
    $this->assertTrue($access->isAllowed(), 'User with multiple permissions can delete');

    $access_control_handler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('su_policy_log');
    $access = $access_control_handler->createAccess(NULL, $multiPermUser, [], TRUE);
    $this->assertTrue($access->isAllowed(), 'User with multiple permissions can create');
  }

  /**
   * Test access with context parameters.
   *
   * // Covers: checkCreateAccess
   */
  public function testCreateAccessWithContext(): void {
    $access_control_handler = $this->container->get('entity_type.manager')
      ->getAccessControlHandler('su_policy_log');

    // Test with empty context.
    $access = $access_control_handler->createAccess(NULL, $this->createUser, [], TRUE);
    $this->assertTrue($access->isAllowed(), 'Create access works with empty context');

    // Test with custom context.
    $context = ['custom_key' => 'custom_value'];
    $access = $access_control_handler->createAccess(NULL, $this->createUser, $context, TRUE);
    $this->assertTrue($access->isAllowed(), 'Create access works with custom context');

    // Test with entity bundle (should be NULL for this entity type).
    $access = $access_control_handler->createAccess(NULL, $this->createUser, [], TRUE);
    $this->assertTrue($access->isAllowed(), 'Create access works with NULL bundle');
  }

  /**
   * Test access results are properly cached.
   *
   * // Covers: checkAccess
   */
  public function testAccessResultCaching(): void {
    // First access check.
    $access1 = $this->policyLog->access('view', $this->viewUser, TRUE);
    $this->assertTrue($access1->isAllowed(), 'First access check returns allowed');

    // Second access check (should use cache).
    $access2 = $this->policyLog->access('view', $this->viewUser, TRUE);
    $this->assertTrue($access2->isAllowed(), 'Second access check returns allowed');

    // Access results should be consistent.
    $this->assertEquals($access1->isAllowed(), $access2->isAllowed(), 'Access results are consistent');
  }

}
