<?php

declare(strict_types=1);

namespace Drupal\stanford_notifications\Hook;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\stanford_notifications\NotificationServiceInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Hooks that integrate with the notification service.
 */
class StanfordNotificationsHooks {

  /**
   * Hook constructor.
   *
   * @param \Drupal\stanford_notifications\NotificationServiceInterface $notificationService
   *   Notification service.
   */
  public function __construct(
    #[Autowire(service: 'notification_service')]
    protected NotificationServiceInterface $notificationService,
  ) {}

  /**
   * Implements hook_toolbar().
   */
  #[Hook('toolbar')]
  public function toolbar(): array {
    return $this->notificationService->toolbar();
  }

  /**
   * Implements hook_ENTITY_TYPE_delete() for user entities.
   */
  #[Hook('user_delete')]
  public function userDelete(EntityInterface $entity): void {
    $this->notificationService->clearUserNotifications($entity);
  }

}
