<?php

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\Core\Session\AccountInterface;
use Drupal\core_event_dispatcher\Event\Theme\PageAttachmentsEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class EventSubscriber.
 *
 * @package Drupal\stanford_profile\EventSubscriber
 */
class CoreEventSubscriber implements EventSubscriberInterface {

  /**
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  public function __construct(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::PAGE_ATTACHMENTS => 'pageAttachments',
    ];
  }

  public function pageAttachments(PageAttachmentsEvent $event) {
    $attachments = &$event->getAttachments();
    $env = getenv('AH_SITE_ENVIRONMENT');
    // Add SiteImprove analytics for anonymous users on prod sites.
    // ACE prod is 'prod'; ACSF can be '01live', '02live', ...
    if (
      $this->currentUser->isAnonymous() &&
      ($env === 'prod' || preg_match('/^\d*live$/', $env))
    ) {
      $attachments['#attached']['library'][] = 'stanford_profile_helper/siteimprove.analytics';
    }
  }

}
