<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\EventSubscriber;

use Drupal\next\Event\EntityActionEvent;
use Drupal\next\Event\EntityEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber for events on decoupled sites.
 *
 * @codeCoverageIgnore
 */
final class DecoupledEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      EntityEvents::ENTITY_ACTION => ['onNextEntityAction', 10],
    ];
  }

  public function __construct() {}

  /**
   * Stop propagation of the event if on local environment and CLI execution.
   *
   * @param \Drupal\next\Event\EntityActionEvent $event
   *   Next module event.
   */
  public function onNextEntityAction(EntityActionEvent $event) {
    // When the site is not on an Acquia environment and running via the CLI, we
    // don't need to do any invalidations. This is often for migration runs.
    if (!getenv('AH_SITE_ENVIRONMENT') && !getenv('PANTHEON_ENVIRONMENT') && PHP_SAPI == 'cli') {
      $event->stopPropagation();
    }
  }

}
