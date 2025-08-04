<?php

namespace Drupal\stanford_policy\Hook;

use Drupal\book\BookManagerInterface;
use Drupal\config_pages\ConfigPagesInterface;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;
use Drupal\stanford_fields\Event\BookOutlineUpdatedEvent;

/**
 * Stanford Policy event subscriber.
 */
class StanfordPolicyHooks {

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\book\BookManagerInterface $bookManager
   *   Book manager service.
   * @param \Drupal\config_pages\ConfigPagesLoaderServiceInterface $configPagesLoader
   *   Config page loader service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(protected BookManagerInterface $bookManager, protected ConfigPagesLoaderServiceInterface $configPagesLoader, protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Alter the policy form widgets.
   */
  #[Hook('field_widget_single_element_form_alter')]
  public function onWidgetFormAlter(array &$element, FormStateInterface $form_state, array $context) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $field_items */
    $field_items = $context['items'];
    if ($field_items->getName() == 'su_policy_related') {
      $element['#chosen'] = TRUE;
    }
  }

  /**
   * Resave all books if the policy config page was saved/deleted.
   */
  #[Hook('config_pages_update')]
  #[Hook('config_pages_insert')]
  #[Hook('config_pages_delete')]
  public function onEntityCrud(ConfigPagesInterface $entity) {
    if ($entity->bundle() == 'policy_settings') {
      $book_node_ids = array_keys($this->bookManager->getAllBooks());
      foreach ($book_node_ids as $node_id) {
        \Drupal::service('stanford_policy.event_subscriber')
          ->resaveBookNodes($node_id);
      }
    }
  }

  /**
   * Reset the policy node label from the other field.
   */
  #[Hook('node_presave')]
  public function onEntityPreSave(NodeInterface $entity): void {
    // Since the settings for the auto entity label have to be "Preserve
    // Existing" so that we don't get errors, we still need to update the node
    // label if the field changed. Use the "Changed" field to determine if this
    // has already been done because the node will be re-saved with the book
    // outline update.
    if (
      $entity->bundle() == 'stanford_policy' &&
      (empty($entity->book['pid']) || $entity->book['pid'] == -1)
    ) {
      $entity->set('title', trim($entity->get('su_policy_title')->getString()));
      $entity->setChangedTime(time());
    }
  }

  /**
   * Alter the book admin form to add submit handler.
   */
  #[Hook('form_alter')]
  public function onFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    if ($form_id == 'book_admin_edit') {
      $build_args = $form_state->getBuildInfo()['args'];
      $book_node = $build_args[0];

      if ($book_node->bundle() == 'stanford_policy') {
        $form['#submit'][] = [self::class, 'onBookAdminEditSubmit'];
      }
    }
    if (in_array($form_id, [
      'node_stanford_policy_form',
      'node_stanford_policy_edit_form',
    ])) {
      $form['su_policy_title']['#attributes']['class'][] = 'js-form-item-title-0-value';
    }
  }

  /**
   * Dispatch the event to update the book outline.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Submitted form state.
   */
  public static function onBookAdminEditSubmit(array &$form, FormStateInterface $form_state): void {
    $build_args = $form_state->getBuildInfo()['args'];
    $book_node = $build_args[0];
    \Drupal::service('event_dispatcher')
      ->dispatch(new BookOutlineUpdatedEvent($book_node), BookOutlineUpdatedEvent::OUTLINE_UPDATED);
  }

}
