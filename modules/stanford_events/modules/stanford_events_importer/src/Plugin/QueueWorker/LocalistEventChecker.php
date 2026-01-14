<?php

declare(strict_types=1);

namespace Drupal\stanford_events_importer\Plugin\QueueWorker;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Queue\Attribute\QueueWorker;
use Drupal\Core\Queue\QueueWorkerBase;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines 'localist_event_checker' queue worker.
 */
#[QueueWorker(
  id: 'localist_event_checker',
  title: new TranslatableMarkup('Localist Event Checker'),
  cron: ['time' => 10],
)]
final class LocalistEventChecker extends QueueWorkerBase implements ContainerFactoryPluginInterface {

  /**
   * Constructs a new LocalistEventChecker instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly ClientInterface $httpClient,
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('http_client'),
      $container->get('entity_type.manager'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function processItem($data): void {
    [$sourceId, $destId] = $data;
    // Fetch from the API. Only delete the destination entity if the API
    // response indicates the event was not found. Don't do anything if there's
    // a timeout or some unexpected error.
    try {
      $this->httpClient->get("https://events.stanford.edu/api/2/events/$sourceId", ['timeout' => 5]);
    }
    catch (ClientException $e) {
      try {
        $errorResponse = json_decode($e->getResponse()->getBody()
          ->getContents(), TRUE, 512, JSON_THROW_ON_ERROR);
        if (
          isset($errorResponse['error']) &&
          str_contains($errorResponse['error'], 'Couldn\'t find Event with')
        ) {
          $this->entityTypeManager->getStorage('node')
            ->load($destId)
            ->delete();
        }
      }
      catch (\Throwable $e) {
        // Do nothing.
      }
    }
  }

}
