<?php

namespace Drupal\stanford_decoupled\Plugin\Next\Revalidator;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\next\Event\EntityActionEvent;
use Drupal\next\Plugin\Next\Revalidator\Path as NextPath;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Overridden next module path plugin to support tokens in the additional paths.
 *
 * @codeCoverageIgnore
 */
class Path extends NextPath {

  /**
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->setDatabase($container->get('database'));
    return $instance;
  }

  /**
   * Set the database service on create.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   Database service.
   *
   * @return $this
   *   Self.
   */
  public function setDatabase(Connection $database) {
    $this->database = $database;
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['method'] = 'GET';
    $config['aggregate'] = FALSE;
    $config['original_additional_paths'] = NULL;
    return $config;
  }

  /**
   * {@inheritDoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['method'] = [
      '#type' => 'radios',
      '#title' => $this->t('Method'),
      '#options' => [
        'GET' => 'GET',
        'POST' => 'POST',
      ],
      '#default_value' => $this->configuration['method'],
    ];
    $form['aggregate'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Aggregate'),
      '#description' => $this->t('Combine all revalidations into a single POST request instead of 1 GET request for each revalidation.'),
      '#default_value' => $this->configuration['aggregate'],
      '#states' => [
        'visible' => [
          ':input[name="method"]' => ['value' => 'POST'],
        ],
      ],
    ];
    return $form;
  }

  /**
   * {@inheritDoc}
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    $this->configuration['method'] = $form_state->getValue('method');
    $this->configuration['aggregate'] = (bool) $form_state->getValue('aggregate');
    parent::submitConfigurationForm($form, $form_state);
  }

  /**
   * {@inheritDoc}
   */
  public function revalidate(EntityActionEvent $event): bool {
    $revalidated = FALSE;
    if ($this->configuration['original_additional_paths'] === NULL) {
      $this->configuration['original_additional_paths'] = $this->configuration['additional_paths'] ?? '';
    }
    $this->configuration['additional_paths'] = self::adjustAdditionalPaths($this->configuration['original_additional_paths'], $event->getEntity());

    if ($this->configuration['method'] != 'POST') {
      return parent::revalidate($event);
    }

    $sites = $event->getSites();
    if (!count($sites)) {
      return FALSE;
    }

    $paths = [];
    if (!empty($this->configuration['revalidate_page'])) {
      $paths[] = $event->getEntityUrl();
    }
    if (!empty($this->configuration['additional_paths'])) {
      $paths = array_merge($paths, array_map('trim', explode("\n", $this->configuration['additional_paths'])));
    }

    if (!count($paths)) {
      return FALSE;
    }

    $tagPaths = array_map(fn($p) => str_replace('/tags/', '', $p), array_filter($paths, fn($path) => str_starts_with($path, '/tags/')));
    $modifiedPaths = array_filter($paths, fn($path) => !str_starts_with($path, '/tags/'));

    $tags = [];
    foreach ($tagPaths as $tagPath) {
      foreach (explode('/', $tagPath) as $tag) {
        $tags[] = $tag;
      }
    }

    asort($modifiedPaths);
    asort($tags);

    $revalidations = [
      'paths' => array_values(array_unique($modifiedPaths)),
      'tags' => array_values(array_unique($tags)),
    ];
    $schema = $this->database->schema();
    /** @var \Drupal\next\Entity\NextSite $site */
    foreach ($sites as $site) {
      if (
        $this->configuration['aggregate'] &&
        $schema->tableExists('stanford_decoupled_revalidation')
      ) {
        foreach ($paths as $path) {
          // Use merge to reduce duplicates.
          $this->database->merge('stanford_decoupled_revalidation')
            ->fields(['site' => $site->id(), 'path' => $path])
            ->keys(['site' => $site->id(), 'path' => $path])
            ->execute();
        }
        continue;
      }

      try {
        $secret = $site->getRevalidateSecret();
        $revalidate_url = Url::fromUri($site->getRevalidateUrl());

        if (!$revalidate_url) {
          throw new \Exception('No revalidate url set.');
        }

        if ($this->nextSettingsManager->isDebug()) {
          $this->logger->notice('(@action): Revalidating path %path for the site %site. URL: %url', [
            '@action' => $event->getAction(),
            '%path' => implode(', ', $paths),
            '%site' => $site->label(),
            '%url' => $revalidate_url->toString(),
          ]);
        }

        $previewConfig = $this->nextSettingsManager->get('preview_url_generator_configuration');
        $headers = ['Authorization' => "Bearer $secret"];
        if ($vercelBypass = $previewConfig['vercel_bypass'] ?? '') {
          $headers['x-vercel-protection-bypass'] = $vercelBypass;
        }

        $response = $this->httpClient->request('POST', $revalidate_url->toString(), [
          'headers' => $headers,
          'json' => $revalidations,
          'timeout' => 5,
        ]);

        if ($response && $response->getStatusCode() === Response::HTTP_OK) {
          if ($this->nextSettingsManager->isDebug()) {
            $this->logger->notice('(@action): Successfully revalidated path %path for the site %site. URL: %url', [
              '@action' => $event->getAction(),
              '%path' => implode(', ', $paths),
              '%site' => $site->label(),
              '%url' => $revalidate_url->toString(),
            ]);
          }

          $revalidated = TRUE;
        }
      }
      catch (\Exception $exception) {
        $this->logger->error($exception->getMessage());
        $revalidated = FALSE;
      }
    }

    return $revalidated;
  }

  /**
   * Convert tokens in the additional paths.
   *
   * @param string|null $paths
   *   Additional paths config.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity being revalidated.
   *
   * @return string|null
   *   Adjusted paths.
   */
  protected static function adjustAdditionalPaths(?string $paths = NULL, ContentEntityInterface $entity): ?string {
    return \Drupal::token()
      ->replacePlain($paths, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
  }

}
