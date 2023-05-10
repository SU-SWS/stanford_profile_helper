<?php

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\core_event_dispatcher\Event\Form\FormBaseAlterEvent;
use Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Form event subscriber.
 */
class FormEventSubscriber implements EventSubscriberInterface {
  use StringTranslationTrait;

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
      HookEventDispatcherInterface::PREFIX . 'form_base_taxonomy_term_form.alter' => ['taxonomyFormAlter'],
    ];
  }

  /**
   * Alter the taxonomy term form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormBaseAlterEvent $event
   *   Alter event.
   */
  public function taxonomyFormAlter(FormBaseAlterEvent $event):void {
    $form = &$event->getForm();
    $form['name']['arg_helper'] = [
      '#type' => 'textfield',
      '#title' => $this->t('List Filtering Argument'),
      '#description' => $this->t('Use this string in the list paragraph to filter for content tagged with this term.'),
      '#default_value' => self::cleanString($form['name']['widget']['0']['value']['#default_value']),
      '#attributes' => ['disabled' => TRUE],
      '#prefix' => '<div id="arg-helper">',
      '#suffix' => '</div>',
      '#states' => [
        'visible' => [
          ':input[name="arg_helper"]' => ['filled' => TRUE],
        ],
      ]
    ];
    $form['name']['widget']['0']['value']['#ajax'] = [
      'callback' => [self::class, 'argHelperAjaxCallback'],
      'wrapper' => 'arg-helper',
      'event' => 'change',
    ];
  }

  /**
   * Ajax callback for the taxonomy term form.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Ajax form state.
   *
   * @return array
   *   Altered arg helper form element.
   */
  public static function argHelperAjaxCallback(array &$form, FormStateInterface $form_state):array {
    $term_name = $form_state->getValue(['name', '0', 'value']);
    $form['name']['arg_helper']['#value'] = self::cleanString($term_name);
    return $form['name']['arg_helper'];
  }

  /**
   * Run the string through path auto alias cleaner.
   *
   * @param string $string
   *   String to clean.
   *
   * @return string
   *   Cleaned string.
   */
  protected static function cleanString(string $string):string {
    return \Drupal::service('pathauto.alias_cleaner')->cleanString($string);
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
