<?php

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\core_event_dispatcher\Event\Entity\EntityBundleFieldInfoAlterEvent;
use Drupal\core_event_dispatcher\Event\Theme\LibraryInfoAlterEvent;
use Drupal\core_event_dispatcher\Event\Theme\PageAttachmentsEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;

/**
 * Class EventSubscriber.
 *
 * @package Drupal\stanford_profile\EventSubscriber
 */
class CoreEventSubscriber extends BaseEventSubscriber {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::LIBRARY_INFO_ALTER => 'libraryInfoAlter',
      HookEventDispatcherInterface::PAGE_ATTACHMENTS => 'pageAttachments',
      HookEventDispatcherInterface::ENTITY_BUNDLE_FIELD_INFO_ALTER => 'entityBundleFieldInfoAlter',
    ];
  }

  /**
   * Alter the library info.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\LibraryInfoAlterEvent $event
   *   Triggered Event.
   *
   * @see hook_library_info_alter()
   */
  public function libraryInfoAlter(LibraryInfoAlterEvent $event) {
    $extension = $event->getExtension();
    $libraries = &$event->getLibraries();

    if ($extension == 'mathjax') {
      $libraries['source']['dependencies'][] = 'stanford_profile_helper/mathjax';
      unset($libraries['setup'], $libraries['config']);
    }

    // Rely on the fontawesome module to provide the library.
    if (
      $extension == 'stanford_basic' &&
      $this->moduleHandler()->moduleExists('fontawesome')
    ) {
      unset($libraries['fontawesome']);
    }
  }

  /**
   * Add library attachments to the pages.
   *
   * @param \Drupal\core_event_dispatcher\Event\Theme\PageAttachmentsEvent $event
   *   Triggered event.
   *
   * @see hook_page_attachments()
   */
  public function pageAttachments(PageAttachmentsEvent $event) {
    $attachments = &$event->getAttachments();
    $env = getenv('AH_SITE_ENVIRONMENT');
    // Add SiteImprove analytics for anonymous users on prod sites.
    // ACE prod is 'prod'; ACSF can be '01live', '02live', ...
    if (
      $this->currentUser()->isAnonymous() &&
      ($env === 'prod' || preg_match('/^\d*live$/', $env))
    ) {
      $attachments['#attached']['library'][] = 'stanford_profile_helper/siteimprove.analytics';
    }
  }

  /**
   * Entity bundle field info alter event actions.
   *
   * @param \Drupal\core_event_dispatcher\Event\Entity\EntityBundleFieldInfoAlterEvent $event
   *   Triggered Event.
   *
   * @see hook_entity_bundle_field_info_alter()
   */
  public function entityBundleFieldInfoAlter(EntityBundleFieldInfoAlterEvent $event) {
    $bundle = $event->getBundle();
    $fields = &$event->getFields();
    if (
      $bundle == 'stanford_global_message' &&
      !empty($fields['su_global_msg_enabled'])
    ) {
      $fields['su_global_msg_enabled']->addConstraint('global_message_constraint', []);
    }
  }

}
