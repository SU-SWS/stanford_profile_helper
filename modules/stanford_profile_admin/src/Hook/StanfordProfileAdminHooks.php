<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_admin\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Admin interface related hooks.
 */
class StanfordProfileAdminHooks {

  /**
   * Implements hook_link_alter().
   */
  #[Hook('link_alter')]
  public function linkAlter(&$variables): void {
    if (
      $variables['url']->isRouted() &&
      ($variables['url']->getRouteName() == 'entity.user.collection' || $variables['url']->getRouteName() == 'user.admin_index')
    ) {
      $variables['text'] = 'Users';
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_menu_link_content_form_alter')]
  public function formMenuLinkContentFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    $form['#attached']['library'][] = 'stanford_profile_admin/menu_link_form';
  }

}
