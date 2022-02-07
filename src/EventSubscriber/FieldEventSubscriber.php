<?php

namespace Drupal\stanford_profile_helper\EventSubscriber;

use Drupal\field_event_dispatcher\Event\Field\WidgetSingleElementFormAlterEvent;
use Drupal\field_event_dispatcher\Event\Field\WidgetSingleElementTypeFormAlterEvent;
use Drupal\hook_event_dispatcher\HookEventDispatcherInterface;

/**
 * Class FieldEventSubscriber.
 *
 * @package Drupal\stanford_profile\EventSubscriber
 */
class FieldEventSubscriber extends BaseEventSubscriber {

  /**
   * {@inheritDoc}
   */
  public static function getSubscribedEvents() {
    return [
      HookEventDispatcherInterface::WIDGET_SINGLE_ELEMENT_FORM_ALTER => 'singleElementFormAlter',
      'hook_event_dispatcher.widget_single_element_entity_reference.alter' => 'entityReferenceWidgetFormAlter',
    ];
  }

  /**
   * Single element widget form alter.
   *
   * @param \Drupal\field_event_dispatcher\Event\Field\WidgetSingleElementFormAlterEvent $event
   *   Triggered Event.
   *
   * @see hook_field_widget_single_element_form_alter().
   */
  public function singleElementFormAlter(WidgetSingleElementFormAlterEvent $event) {
    $context = $event->getContext();
    $element = &$event->getElement();

    if ($context['items']->getName() == 'su_page_components') {
      // Push pages to only allow 3 items per row but don't break any existing
      // pages that have 4 per row.
      $element['container']['value']['#attached']['drupalSettings']['reactParagraphs'][0]['itemsPerRow'] = 3;
    }

    if ($context['items']->getName() == 'field_media_embeddable_oembed') {
      $user = $this->currentUser();
      $authorized = $user->hasPermission('create field_media_embeddable_code')
        || $user->hasPermission('edit field_media_embeddable_code');
      if (!$authorized) {
        $args = $element['value']['#description']['#items'][1]->getArguments();
        $args['@snow_form'] = 'https://stanford.service-now.com/it_services?id=sc_cat_item&sys_id=83daed294f4143009a9a97411310c70a';
        $new_desc = 'Allowed providers: @providers. For custom embeds, please <a href="@snow_form">request support.</a>';
        $element['value']['#description'] = $this->t($new_desc, $args);
      }
    }
  }

  /**
   * Alter the entity reference widget form.
   *
   * @param \Drupal\field_event_dispatcher\Event\Field\WidgetSingleElementTypeFormAlterEvent $event
   *   Triggered Event.
   *
   * @see hook_field_widget_single_element_WIDGET_TYPE_form_alter().
   */
  public function entityReferenceWidgetFormAlter(WidgetSingleElementTypeFormAlterEvent $event) {
    $context = $event->getContext();
    $element = &$event->getElement();
    if ($context['items']->getFieldDefinition()
        ->getName() == 'layout_selection') {
      $element['#description'] = $this->t('Choose a layout to display the page as a whole. Choose "- None -" to keep the default layout setting.');
    }
  }

}
