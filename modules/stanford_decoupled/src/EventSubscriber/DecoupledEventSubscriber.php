<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\EventSubscriber;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\next\Event\EntityActionEvent;
use Drupal\next\Event\EntityEvents;
use Drupal\stanford_profile_helper\Event\MenuCacheEvent;
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
      MenuCacheEvent::CACHE_CLEARED => ['onMenuCacheClear'],
      EntityEvents::ENTITY_ACTION => ['onNextEntityAction', 10],
    ];
  }

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager) {}

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

  /**
   * Invalidate next menu caches after the drupal menus cache is cleared.
   *
   * @param \Drupal\stanford_profile_helper\Event\MenuCacheEvent $event
   *   Triggered event.
   */
  public function onMenuCacheClear(MenuCacheEvent $event) {
    $fake_menu_link = $this->entityTypeManager->getStorage('menu_link_content')
      ->create(['id' => 'id']);
    next_entity_insert($fake_menu_link);
  }

}
