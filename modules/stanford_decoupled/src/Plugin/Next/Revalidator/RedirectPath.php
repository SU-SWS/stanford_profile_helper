<?php

namespace Drupal\stanford_decoupled\Plugin\Next\Revalidator;

use Drupal\Core\Form\FormStateInterface;
use Drupal\next\Event\EntityActionEvent;

/**
 * Provides a revalidator for redirect paths.
 *
 * @codeCoverageIgnore
 *
 * @Revalidator(
 *  id = "redirect_path",
 *  label = "Redirect Path",
 *  description = "Path-based on-demand revalidation."
 * )
 */
class RedirectPath extends Path {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['revalidate_page'] = TRUE;
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['revalidate_page']['#access'] = FALSE;
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function revalidate(EntityActionEvent $event): bool {
    $event->setEntityUrl('/' . $event->getEntity()->getSource()['path']);
    return parent::revalidate($event);
  }

}
