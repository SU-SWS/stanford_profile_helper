<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hooks that relate to media library embeddable code.
 */
class MediaHooks {

  use StringTranslationTrait;

  /**
   * Media hook constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current active user.
   */
  public function __construct(protected AccountProxyInterface $currentUser) {}

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_media_library_add_form_embeddable_alter')]
  public function formMediaLibraryAddFormEmbeddableAlter(array &$form, FormStateInterface $form_state): void {
    $source_field = $form_state->get('source_field');
    $embed_code_field = $form_state->get('unstructured_field_name');
    $authorized = $this->currentUser->hasPermission('create field_media_embeddable_code')
      || $this->currentUser->hasPermission('edit field_media_embeddable_code');

    if (isset($form['container'][$embed_code_field])) {
      $form['container'][$embed_code_field]['#access'] = $authorized;
    }

    if (isset($form['container'][$source_field])) {
      if (!$authorized) {
        $new_desc = 'Allowed providers: @providers. For custom embeds, please <a href="@snow_form">request support.</a>';
        $args = $form['container'][$source_field]['#description']->getArguments();
        $args['@snow_form'] = 'https://stanford.service-now.com/it_services?id=sc_cat_item&sys_id=83daed294f4143009a9a97411310c70a';
        $form['container'][$source_field]['#description'] = $this->t($new_desc, $args);
      }
      $form['container'][$source_field]['#title'] = $this->t('oEmbed URL');
    }
  }

  /**
   * Implements hook_field_widget_complete_form_alter().
   *
   * Restricts the embeddable oEmbed URL description/help text to users who
   * are authorized to manage the embeddable code field directly.
   */
  #[Hook('field_widget_complete_form_alter')]
  public function fieldWidgetCompleteFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context): void {
    if ($context['items']->getName() == 'field_media_embeddable_oembed') {
      $authorized = $this->currentUser->hasPermission('create field_media_embeddable_code')
        || $this->currentUser->hasPermission('edit field_media_embeddable_code');

      if (!$authorized) {
        $args = $field_widget_complete_form['widget'][0]['value']['#description']['#items'][1]->getArguments();
        $args['@snow_form'] = 'https://stanford.service-now.com/it_services?id=sc_cat_item&sys_id=83daed294f4143009a9a97411310c70a';
        $new_desc = 'Allowed providers: @providers. For custom embeds, please <a href="@snow_form">request support.</a>';
        $field_widget_complete_form['widget'][0]['value']['#description'] = $this->t($new_desc, $args);
      }
    }
  }

}
