<?php

namespace Drupal\stanford_decoupled\Plugin\Next\Revalidator;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\Url;
use Drupal\next\Event\EntityActionEvent;
use Drupal\next\NextSettingsManagerInterface;
use Drupal\next\Plugin\Next\Revalidator\Path as NextPath;
use GuzzleHttp\ClientInterface;
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
    $this->configuration['additional_paths'] = self::adjustAdditionalPaths($this->configuration['additional_paths'], $event->getEntity());
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

    $modifiedPaths = [];
    $tags = [];

    foreach ($paths as $path) {
      if (!str_starts_with($path, '/tags/')) {
        $modifiedPaths[] = $path;
        continue;
      }
      foreach (explode('/', str_replace('/tags/', '', $path)) as $tag) {
        $tags[] = $tag;
      }
    }

    asort($modifiedPaths);
    asort($tags);

    $revalidations = [
      'paths' => array_values(array_unique($modifiedPaths)),
      'tags' => array_values(array_unique($tags)),
    ];

    /** @var \Drupal\next\Entity\NextSite $site */
    foreach ($sites as $site) {
      if (
        $this->configuration['aggregate'] &&
        $this->database->schema()->tableExists('stanford_decoupled_revalidation')
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

        $response = $this->httpClient->request('POST', $revalidate_url->toString(), [
          'headers' => ['Authorization' => "Bearer $secret"],
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
   * @param string $paths
   *   Additional paths config.
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   Entity being revalidated.
   *
   * @return string
   *   Adjusted paths.
   */
  protected static function adjustAdditionalPaths(string $paths, ContentEntityInterface $entity) {
    return \Drupal::token()
      ->replace($paths, [$entity->getEntityTypeId() => $entity], ['clear' => TRUE]);
  }

}
