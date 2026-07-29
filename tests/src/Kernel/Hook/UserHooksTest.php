<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\Hook;

use Drupal\KernelTests\KernelTestBase;
use Drupal\user\Entity\Role;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Class EventSubscriberTest.
 */
#[RunTestsInSeparateProcesses]
class UserHooksTest extends KernelTestBase {

  protected $profile = 'stanford_profile';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'node',
    'user',
    'samlauth',
    'externalauth',
    'stanford_profile_helper',
    'file',
    'config_pages',
  ];

  /**
   * {@inheritDoc}
   */
  public function setup(): void {
    parent::setUp();
    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installConfig('samlauth');
  }

  public function testUserInsert() {
    $role = Role::create(['id' => 'test_role1', 'label' => 'Test role 1']);
    $role->save();

    $saml_setting = \Drupal::config('samlauth.authentication')
      ->get('map_users_roles');

    $this->assertContains('test_role1', $saml_setting);
  }

}
