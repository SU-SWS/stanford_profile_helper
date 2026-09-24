<?php

namespace Drupal\Tests\stanford_notifications\Kernel\Drush\Commands;

use Drupal\stanford_notifications\Drush\Commands\StanfordNotificationsCommands;
use Drupal\Tests\stanford_notifications\Kernel\StanfordNotificationTestBase;
use Drush\Exceptions\UserAbortException;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Class StanfordNotificationsCommandsTest.
 */
#[RunTestsInSeparateProcesses]
#[Group('stanford_notifications')]
class StanfordNotificationsCommandsTest extends StanfordNotificationTestBase {

  /**
   * Drush command should make some entities.
   */
  public function testAddDrushCommand() {
    $this->createUser();
    $commands = StanfordNotificationsCommands::create($this->container);
    $commands->addNotification('Foo Bar');
    $notifications = \Drupal::entityTypeManager()->getStorage('notification')->loadMultiple();
    $this->assertCount(1, $notifications);

    $this->expectException(UserAbortException::class);
    $commands->addNotification('Foo', ['roles' => '', 'status' => 'foo']);
  }

  /**
   * Roles in the option are trimmed so only users with those roles are sent.
   */
  public function testAddDrushCommandRoles() {
    $foo_user = $this->createUser('foo');
    $bar_user = $this->createUser('bar');
    $this->createUser();

    $commands = StanfordNotificationsCommands::create($this->container);
    $commands->addNotification('Foo Bar', [
      'roles' => ' foo , bar',
      'status' => 'warning',
    ]);

    $notifications = \Drupal::entityTypeManager()
      ->getStorage('notification')
      ->loadMultiple();
    $user_ids = [];
    foreach ($notifications as $notification) {
      $this->assertEquals('warning', $notification->get('status')->getString());
      $user_ids[] = (int) $notification->get('uid')->getString();
    }
    sort($user_ids);
    $this->assertEquals([(int) $foo_user->id(), (int) $bar_user->id()], $user_ids);
  }

}
