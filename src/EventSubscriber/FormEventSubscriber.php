<?php

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\core_event_dispatcher\Event\Form\FormAlterEvent;
use Drupal\core_event_dispatcher\Event\Form\FormBaseAlterEvent;
use Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;
use Drupal\node\NodeForm;

/**
 * Class FormEventSubscriber for all form altering events.
 */
class FormEventSubscriber extends BaseEventSubscriber {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    $base = 'hook_event_dispatcher.form_';
    return [
      HookEventDispatcherInterface::FORM_ALTER => 'formAlter',
      $base . 'menu_edit_form.alter' => 'menuEditFormAlter',
      $base . 'taxonomy_overview_terms.alter' => 'taxonomyOverviewTermsFormAlter',
      $base . 'views_bulk_operations_configure_action.alter' => 'vboConfigActionFormAlter',
      $base . 'base_taxonomy_term_form.alter' => 'taxonomyTermFormAlter',
      $base . 'config_pages_stanford_basic_site_settings_form.alter' => 'siteSettingFormAlter',
      $base . 'config_pages_lockup_settings_form.alter' => 'lockupSettingFormAlter',
      $base . 'config_pages_stanford_local_footer_form.alter' => 'localFooterFormAlter',
      $base . 'media_library_add_form_embeddable.alter' => 'embeddableMediaFormAlter',
    ];
  }

  /**
   * Alter the config pages site settings form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   Triggered event.
   */
  public function siteSettingFormAlter(FormIdAlterEvent $event) {
    $form = &$event->getForm();
    $form['#validate'][] = [self::class, 'siteSettingFormValidate'];
  }

  /**
   * Site settings form validation.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   */
  public static function siteSettingFormValidate(array $form, FormStateInterface $form_state) {
    $element = $form_state->getValue('su_site_url');
    $uri = $element['0']['uri'];
    if (!empty($uri)) {
      // Test if the site url submmitted is equal to current domain.
      $host = \Drupal::request()->getSchemeAndHttpHost();
      if ($host != $uri) {
        $form_state->setErrorByName('su_site_url', t('This URL does not match your domain.'));
      }
    }
  }

  /**
   * Alter the config pages lockup settings form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   Triggered event.
   */
  public function lockupSettingFormAlter(FormIdAlterEvent $event) {
    $form = &$event->getForm();
    // Clear caches on submit.
    $form['actions']['submit']['#submit'][] = [
      self::class,
      'invalidateSystemCache',
    ];
  }

  /**
   * Alter the config pages local footer form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   Triggered Event.
   */
  public function localFooterFormAlter(FormIdAlterEvent $event) {
    $form = &$event->getForm();
    $form['actions']['submit']['#submit'][] = [
      self::class,
      'invalidateSystemCache',
    ];
  }

  /**
   * Alter the embeddable media form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   Triggered event.
   */
  public function embeddableMediaFormAlter(FormIdAlterEvent $event) {
    $form = &$event->getForm();
    $form_state = $event->getFormState();

    $source_field = $form_state->get('source_field');
    $embed_code_field = $form_state->get('unstructured_field_name');

    $create_perm = $this->currentUser()
      ->hasPermission('create field_media_embeddable_code');
    $edit_perm = $this->currentUser()
      ->hasPermission('edit field_media_embeddable_code');

    if (isset($form['container'][$embed_code_field])) {
      $form['container'][$embed_code_field]['#access'] = $create_perm || $edit_perm;
    }

    if (isset($form['container'][$source_field])) {
      if (!($create_perm || $edit_perm)) {
        $args = $form['container'][$source_field]['#description']->getArguments();
        $args['@snow_form'] = 'https://stanford.service-now.com/it_services?id=sc_cat_item&sys_id=83daed294f4143009a9a97411310c70a';
        $form['container'][$source_field]['#description'] = $this->t('Allowed providers: @providers. For custom embeds, please <a href="@snow_form">request support.</a>', $args);
      }
      $form['container'][$source_field]['#title'] = $this->t('oEmbed URL');
    }
  }

  /**
   * Alter the taxonomy term form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormBaseAlterEvent $event
   *   Triggered Event.
   */
  public function taxonomyTermFormAlter(FormBaseAlterEvent $event) {
    // Tweak the taxonomy term add/edit form.
    $form = &$event->getForm();
    if (!empty($form['relations']['parent'])) {
      $form['relations']['#open'] = TRUE;
      $form['relations']['parent']['#multiple'] = FALSE;
      $form['relations']['parent']['#title'] = $this->t('Parent term');
      $form['relations']['parent']['#description'] = $this->t('Select the appropriate parent item for this term.');
      $form['relations']['parent']['#element_validate'][] = [
        self::class,
        'taxonomyTermFormSubmit',
      ];
    }
  }

  /**
   * Tweak the taxonomy term parent form value after submitting.
   *
   * Because we are changing the form to not allow multiple parents, the form
   * value needs to be changed into an array so the TermForm can still manage
   * it correctly.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state object.
   * @param array $form
   *   Complete form.
   *
   * @see stanford_profile_helper_form_taxonomy_term_form_alter()
   */
  public static function taxonomyTermFormSubmit(array $element, FormStateInterface $form_state, array $form) {
    $form_state->setValueForElement($element, [$element['#value']]);
  }

  /**
   * Alter the VBO config action form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   Triggered Event.
   */
  public function vboConfigActionFormAlter(FormIdAlterEvent $event) {
    $form = &$event->getForm();
    if (!empty($form['node']['stanford_event']['su_event_date_time']['widget'])) {
      $form['node']['stanford_event']['su_event_date_time']['widget']['#required'] = FALSE;
      $form['node']['stanford_event']['su_event_date_time']['widget'][0]['#required'] = FALSE;
      $form['node']['stanford_event']['su_event_date_time']['widget'][0]['end_value']['#required'] = FALSE;
    }
  }

  /**
   * Alter any form, provide some logic to identify the desired forms.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormAlterEvent $event
   *   Triggered Event.
   */
  public function formAlter(FormAlterEvent $event) {
    $form = &$event->getForm();
    $form_state = $event->getFormState();
    $form_id = $event->getFormId();

    if ($form_state->getFormObject() instanceof NodeForm) {
      unset($form['actions']['unlock']);
    }
    if (strpos($form_id, 'views_form_') === 0) {
      // Remove the select all since it selects every node, not just the ones
      // from the active filters.
      // @link https://www.drupal.org/project/views_bulk_operations/issues/3055770#comment-13116724
      unset($form['header']['views_bulk_operations_bulk_form']['select_all']);

      // Sort the action menu options alphabetically.
      if (!empty($form['header']['views_bulk_operations_bulk_form']['action']['#options'])) {
        $actions_array = $form['header']['views_bulk_operations_bulk_form']['action']['#options'];
        uasort($actions_array, function ($a, $b) {
          return strcasecmp((string) $a, (string) $b);
        });
        $form['header']['views_bulk_operations_bulk_form']['action']['#options'] = $actions_array;
      }
    }
  }

  /**
   * Alter the menu edit form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   Triggered Event.
   */
  public function menuEditFormAlter(FormIdAlterEvent $event) {
    $form = &$event->getForm();

    $read_only = Settings::get('config_readonly', FALSE);
    if (!$read_only) {
      return;
    }

    // If the form is locked, hide the config you cannot change from users
    // without the know how.
    $access = $this->currentUser()
      ->hasPermission('administer menus and menu items');
    $form['label']['#access'] = $access;
    $form['description']['#access'] = $access;
    $form['id']['#access'] = $access;

    // Remove the warning message if the user does not have access.
    if (!$access) {
      $this->messenger()->deleteByType("warning");
    }
  }

  /**
   * Alter the taxonomy overview terms form.
   *
   * @param \Drupal\core_event_dispatcher\Event\Form\FormIdAlterEvent $event
   *   Triggered Event.
   */
  public function taxonomyOverviewTermsFormAlter(FormIdAlterEvent $event) {
    $form = &$event->getForm();
    $form_state = $event->getFormState();

    if ($form_state->get('taxonomy')['vocabulary']->id() == 'stanford_publication_topics') {
      $url = Url::fromUri('https://userguide.sites.stanford.edu/tour/publications#publications-list-page');
      $link = Link::fromTextAndUrl($this->t('default Publications List Page'), $url)
        ->toString();
      $form['citation_format']['#title'] = $this->t('Citation Style');
      $form['citation_format']['#description'] = $this->t('Select citation format for the %link. *<strong>CAUTION</strong>: The default Publication list page uses Chicago as the citation style. If you select a different citation format here, you should also update the citation format on the default Publications List Page that uses a "filter by topics" menu.', ['%link' => $link]);
    }
  }

  /**
   * Invalidate cache tags when submitting a form.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   */
  public static function invalidateSystemCache(array &$form, FormStateInterface $form_state) {
    Cache::invalidateTags(['config:system.site']);
  }

}
