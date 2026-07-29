<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_notifications\Unit\Hook;

use Drupal\stanford_notifications\Hook\StanfordNotificationsHooks;
use Drupal\stanford_notifications\NotificationServiceInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\user\UserInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for StanfordNotificationsHooks.
 */
#[Group('stanford_notifications')]
#[CoversClass(StanfordNotificationsHooks::class)]
class StanfordNotificationsHooksTest extends UnitTestCase {

  /**
   * Mocked notification service.
   *
   * @var \Drupal\stanford_notifications\NotificationServiceInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected NotificationServiceInterface $notificationService;

  /**
   * The hooks class under test.
   *
   * @var \Drupal\stanford_notifications\Hook\StanfordNotificationsHooks
   */
  protected StanfordNotificationsHooks $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->notificationService = $this->createMock(NotificationServiceInterface::class);
    $this->hooks = new StanfordNotificationsHooks($this->notificationService);
  }

  /**
   * The toolbar hook delegates to the notification service and returns its
   * render array.
   */
  public function testToolbar(): void {
    $expected = ['#cache' => [], '#type' => 'toolbar_item'];
    $this->notificationService->expects($this->once())
      ->method('toolbar')
      ->willReturn($expected);

    $this->assertSame($expected, $this->hooks->toolbar());
  }

  /**
   * The user delete hook delegates to the notification service to clear the
   * deleted user's notifications.
   */
  public function testUserDelete(): void {
    // The user_delete hook is only ever invoked with a User entity, which
    // implements both EntityInterface (the hook's parameter type) and
    // AccountInterface (what the notification service expects).
    $entity = $this->createMock(UserInterface::class);
    $this->notificationService->expects($this->once())
      ->method('clearUserNotifications')
      ->with($entity);

    $this->hooks->userDelete($entity);
  }

}
