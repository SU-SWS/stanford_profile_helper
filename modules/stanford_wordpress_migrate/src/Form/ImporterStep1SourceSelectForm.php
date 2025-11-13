<?php

namespace Drupal\stanford_wordpress_migrate\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Form\FormStateInterface;

/**
 * Simple wizard step form.
 */
class ImporterStep1SourceSelectForm extends WordPressImporterFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'entity.wordpress_migration.step_1';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $cached_values = $form_state->getTemporaryValue(['wizard']);
    /** @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration */
    $migration = $cached_values['wordpress_migration'];

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Site Name'),
      '#required' => TRUE,
      '#default_value' => $migration->label(),
    ];
    $form['base_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base url of the WordPress site'),
      '#description' => $this->t("For example: 'https://test.example.com'. Do not include trailing slashes."),
      '#required' => TRUE,
      '#default_value' => $migration->getBaseUrl(),
      '#disabled' => !!$migration->getBaseUrl(),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    // Make sure the provided value is actually a URL & is external.
    $url = $form_state->getValue('base_url');
    if (!UrlHelper::isValid($url, TRUE)) {
      $form_state->setError($form['base_url'], $this->t('Provided value is not a url.'));
      return;
    }
    elseif (!UrlHelper::isExternal($url)) {
      $form_state->setError($form['base_url'], $this->t('Provided URL must be an external site.'));
      return;
    }

    // If the API endpoint doesn't exist, the site might not be WordPress.
    try {
      $api_routes = $this->getApiEndpoints($url);
    }
    catch (\Exception $e) {
      $form_state->setError($form['base_url'], $this->t('Provided URL is not a WordPress site.'));
      return;
    }
    $form_state->set('wp-api-routes', $api_routes);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $temp_values = $form_state->getTemporaryValue(['wizard']);
    $temp_values['wordpress_migration']->set('label', $form_state->getValue('label'));
    $temp_values['wordpress_migration']->set('base_url', $form_state->getValue(['base_url']));
    $temp_values['api-routes'] = $form_state->get('wp-api-routes');
    $form_state->setTemporaryValue('wizard', $temp_values);
  }

}
