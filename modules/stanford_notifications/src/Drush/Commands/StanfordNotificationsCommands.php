<?php

namespace Drupal\stanford_notifications\Drush\Commands;

use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\stanford_notifications\NotificationServiceInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Drush\Exceptions\UserAbortException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Stanford Notifications Drush commands.
 */
class StanfordNotificationsCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * StanfordNotificationsCommands constructor.
   *
   * @param \Drupal\stanford_notifications\NotificationServiceInterface $notificationService
   *   Notification service.
   */
  public function __construct(
    #[Autowire(service: 'notification_service')]
    protected NotificationServiceInterface $notificationService,
  ) {
    parent::__construct();
  }

  /**
   * Add a new notification to users on the site.
   *
   * @param string $message
   *   Notification message for the user.
   * @param array $options
   *   Keyed array of options.
   *
   * @throws \Drush\Exceptions\UserAbortException
   */
  #[CLI\Command(name: 'stanford:add-notification')]
  #[CLI\Argument(name: 'message', description: 'Notification message for the user.')]
  #[CLI\Option(name: 'roles', description: 'Comma delimited list of roles to set the notification for.')]
  #[CLI\Option(name: 'status', description: "Notification status type, 'status', 'warning', or 'error'.")]
  public function addNotification(string $message, array $options = [
    'roles' => '',
    'status' => MessengerInterface::TYPE_STATUS,
  ]): void {
    $status_options = [
      MessengerInterface::TYPE_STATUS,
      MessengerInterface::TYPE_WARNING,
      MessengerInterface::TYPE_ERROR,
    ];

    if (!in_array($options['status'], $status_options)) {
      throw new UserAbortException((string) new TranslatableMarkup('Invalid status. Please use one of the options: @options', ['@options' => implode(', ', $status_options)]));
    }
    $roles = array_map('trim', explode(',', $options['roles']));
    $this->notificationService->addNotification($message, array_filter($roles), $options['status']);
  }

}
