<?php

namespace Drupal\stanford_decoupled\Drush\Commands;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * A Drush commandfile.
 *
 * @codeCoverageIgnore No need to test drush commands.
 */
final class StanfordDecoupledCommands extends DrushCommands {

  /**
   * Constructs a StanfordDecoupledCommands object.
   */
  public function __construct(private readonly EntityTypeManagerInterface $entityTypeManager, private readonly UuidInterface $uuid) {}

  /**
   * Create a next site entity to connect with Next.JS site
   */
  #[CLI\Command(name: 'stanford-decoupled:connect-next', aliases: ['su-next-connect'])]
  #[CLI\Argument(name: 'domain', description: 'Next.js Domain.')]
  #[CLI\Option(name: 'preview-secret', description: 'Use a specific preview secret')]
  #[CLI\Option(name: 'revalidation-secret', description: 'Use a specific revalidation secret')]
  #[CLI\Option(name: 'format', description: 'Format the result data. Available formats: json,string')]
  #[CLI\Usage(name: 'stanford-decoupled:connect-next "https://localhost:3000"', description: 'Create a next site entity to connect with Next.JS site')]
  public function connectNextSite($domain, $options = [
    'id' => 'local',
    'preview-secret' => NULL,
    'revalidation-secret' => NULL,
    'format' => 'string',
  ]
  ) {
    $domain = trim($domain);
    if (!UrlHelper::isValid($domain, TRUE)) {
      throw new \Exception('Invalid domain');
    }

    $site_storage = $this->entityTypeManager->getStorage('next_site');
    /** @var \Drupal\next\Entity\NextSiteInterface $site */
    $site = $site_storage->load($options['id']);
    if (!$site) {
      $site = $site_storage->create([
        'id' => $options['id'],
        'label' => $options['id'],
      ]);
    }
    $site->setPreviewSecret($options['preview-secret'] ?: $this->uuid->generate());
    $site->setRevalidateSecret($options['revalidation-secret'] ?: $this->uuid->generate());
    $site->setBaseUrl($domain);
    $site->setRevalidateUrl("$domain/api/revalidate");
    $site->setPreviewUrl("$domain/api/draft");

    $site->save();

    if ($options['format'] == 'json') {
      $this->output()->write(json_encode([
        'id' => $options['id'],
        'preview_secret' => $site->getPreviewSecret(),
        'revalidation_secret' => $site->getRevalidateSecret(),
      ]));
      return;
    }
    $this->output()
      ->write('ID: ' . $options['id'] . PHP_EOL . 'Preview Secret: ' . $site->getPreviewSecret() . PHP_EOL . 'Revalidation Secret:' . $site->getRevalidateSecret() . PHP_EOL);
  }

}
