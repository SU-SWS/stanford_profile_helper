<?php

declare(strict_types=1);

namespace Drupal\stanford_wordpress_migrate\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Simple wizard step form.
 */
abstract class WordPressImporterFormBase extends FormBase {

  public function __construct(
    protected ClientInterface $client
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'wordpress_importer_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $cached_values = $form_state->getTemporaryValue(['wizard']);

    // Call the base API route to list all possible endpoints.
    if (!$form_state->getTemporaryValue(['wizard', 'api-routes'])) {
      /** @var \Drupal\stanford_wordpress_migrate\WordPressMigrationInterface $migration */
      $migration = $cached_values['wordpress_migration'];

      if ($baseUrl = $migration->getBaseUrl()) {
        $endpoints = $this->getApiEndpoints($baseUrl);
        $form_state->setTemporaryValue(['wizard', 'api-routes'], $endpoints);
      }
    }
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * Get an array of possible API endpoints as provided by the base API route.
   *
   * @param string $baseUrl
   *   Site domain.
   *
   * @return array
   *   Associative array of API routes with an appropriate label.
   */
  protected function getApiEndpoints(string $baseUrl): array {
    try {
      $api_response = $this->client()
        ->request('GET', "$baseUrl/wp-json/wp/v2", ['timeout' => 5]);
      $api_routes = json_decode((string) $api_response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR);

      $api_routes = array_filter(array_keys($api_routes['routes']), fn($route) => preg_match('/^\w+$/', str_replace('/wp/v2/', '', $route)));
      $api_routes = array_combine($api_routes, $api_routes);
      foreach ($api_routes as &$route) {
        $route = ucwords(str_replace('_', ' ', basename($route)));
      }
      asort($api_routes);
      return $api_routes;
    }
    catch (\Throwable $e) {
      return [];
    }
  }

  /**
   * Get guzzle client.
   *
   * @return \GuzzleHttp\ClientInterface
   *   Client service.
   */
  protected function client() {
    if (isset($this->client)) {
      return $this->client;
    }
    return \Drupal::httpClient();
  }

  /**
   * Ajax callback to add another form mapping.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   *
   * @return array
   *   Ajax element.
   */
  public static function addAnotherAjax(array &$form, FormStateInterface $form_state): array {
    $element_key = $form_state->getTriggeringElement()['#add-more'];
    return $form[$element_key];
  }

  /**
   * Ajax functionality to increase form mappings.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   */
  public static function addAnother(array &$form, FormStateInterface $form_state) {
    $count = $form_state->get('num_mappings');
    $form_state->set('num_mappings', $count + 1);
    $form_state->setRebuild();
  }

}
