<?php

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Form event subscriber.
 */
class FormEventSubscriber implements EventSubscriberInterface {

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user account.
   */
  public function __construct(protected AccountProxyInterface $currentUser) {}

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::PREFIX . 'form_taxonomy_overview_vocabularies.alter' => ['taxonomyOverviewFormAlter'],
    ];
  }

  /**
   * Modify the taxonomy overview form to hide vocabs the user doesn't need.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   Triggered event.
   */
  public function taxonomyOverviewFormAlter(FormIdAlterEvent $event): void {
    if ($this->currentUser->hasPermission('administer taxonomy')) {
      return;
    }

    $form = &$event->getForm();
    foreach (Element::children($form['vocabularies']) as $vid) {
      if (
        !$this->currentUser->hasPermission("create terms in $vid") &&
        !$this->currentUser->hasPermission("delete terms in $vid") &&
        !$this->currentUser->hasPermission("edit terms in $vid")
      ) {
        unset($form['vocabularies'][$vid]);
        continue;
      }
      unset($form['vocabularies'][$vid]['weight']);
    }
    unset($form['vocabularies']['#tabledrag']);
    unset($form['vocabularies']['#header']['weight'], $form['actions']);
  }

}
