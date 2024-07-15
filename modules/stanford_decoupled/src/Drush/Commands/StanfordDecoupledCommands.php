<?php

namespace Drupal\stanford_decoupled\Drush\Commands;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Url;
use Drupal\next\Event\EntityActionEvent;
use Drupal\next\Event\EntityActionEventInterface;
use Drupal\next\Event\EntityEvents;
use Drush\Attributes as CLI;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Drush commands used for various tasks within a decoupled site.
 *
 * @codeCoverageIgnore No need to test drush commands.
 */
#[CLI\Bootstrap(DrupalBootLevels::FULL)]
final class StanfordDecoupledCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * Constructs a StanfordDecoupledCommands object.
   */
  public function __construct(private readonly EntityTypeManagerInterface $entityTypeManager, private readonly UuidInterface $uuid, private readonly PasswordGeneratorInterface $passwordGenerator, private readonly EventDispatcherInterface $eventDispatcher) {
    parent::__construct();
  }

  /**
   * Create a next site entity to connect with NextJS site
   */
  #[CLI\Command(name: 'stanford-decoupled:connect-next', aliases: ['su-next-connect'])]
  #[CLI\Argument(name: 'domain', description: 'Next.js Domain.')]
  #[CLI\Option(name: 'preview-secret', description: 'Use a specific preview secret')]
  #[CLI\Option(name: 'revalidation-secret', description: 'Use a specific revalidation secret')]
  #[CLI\Option(name: 'format', description: 'Format the result data. Available formats: json,string')]
  #[CLI\Usage(name: 'stanford-decoupled:connect-next "http://localhost:3000"', description: 'Create a next site entity to connect with Next.JS site')]
  public function connectNextSite($domain = 'http://localhost:3000', $options = [
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

    $nextjs_pass = $this->passwordGenerator->generate(20);
    $nextjs_users = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['name' => 'nextjs']);
    if (!$nextjs_users) {
      $nextjs_users = [
        $this->entityTypeManager->getStorage('user')
          ->create(['name' => 'nextjs', 'status' => TRUE]),
      ];
    }
    /** @var \Drupal\user\Entity\User $nextjs_user */
    $nextjs_user = reset($nextjs_users);
    $nextjs_user->addRole('decoupled_site_users')
      ->setPassword($nextjs_pass)
      ->save();

    $nextjs_admin_pass = $this->passwordGenerator->generate(20);
    $nextjs_admin_users = $this->entityTypeManager->getStorage('user')
      ->loadByProperties(['name' => 'nextjsAdmin']);
    if (!$nextjs_admin_users) {
      $nextjs_admin_users = [
        $this->entityTypeManager->getStorage('user')
          ->create(['name' => 'nextjsAdmin', 'status' => TRUE]),
      ];
    }
    $nextjs_admin_user = reset($nextjs_admin_users);
    $nextjs_admin_user->addRole('decoupled_site_users')
      ->addRole('administrator')
      ->setPassword($nextjs_admin_pass)
      ->save();

    $output = [
      'DRUPAL_PREVIEW_SECRET' => $site->getPreviewSecret(),
      'DRUPAL_REVALIDATE_SECRET' => $site->getRevalidateSecret(),
      'DRUPAL_BASIC_AUTH' => 'nextjs:' . $nextjs_pass,
      'DRUPAL_BASIC_AUTH_ADMIN' => 'nextjsAdmin:' . $nextjs_admin_pass,
    ];

    if ($options['format'] == 'json') {
      $this->output()->write(json_encode($output, JSON_PRETTY_PRINT));
      return;
    }

    $lined_output = [];
    foreach ($output as $key => $value) {
      $lined_output[] = "$key=$value";
    }
    $this->output()->write(implode(PHP_EOL, $lined_output) . PHP_EOL);
  }

  /**
   * Send invalidation requests to the front end for the given url.
   */
  #[CLI\Command(name: 'stanford-decoupled:invalidate-url', aliases: ['su-next-invalidate'])]
  #[CLI\Argument(name: 'url', description: 'Relative URL string, starting with "/"')]
  public function nextInvalidateUrl(string $url) {
    try {
      $route_params = Url::fromUserInput(trim($url))
        ->getRouteParameters();
    }
    catch (\Exception $e) {
      // Redirect urls don't have route parameters while all other entity types
      // do. If the URL fails to get the parameters, let's look to see if there
      // is a redirect that matches the provided path.
      $redirect = $this->entityTypeManager->getStorage('redirect')
        ->loadByProperties(['redirect_source' => ltrim($url, '/')]);

      // No redirect and no entity that matches the provided url.
      if (!$redirect) {
        throw new \Exception('Provided url does not exist. Ensure the path is relative url and exists on the site.');
      }
      $route_params = ['redirect' => reset($redirect)->id()];
    }

    $entity_type = key($route_params);
    $entity_id = $route_params[$entity_type];

    if (!$this->entityTypeManager->hasDefinition($entity_type)) {
      throw new \Exception('Unknown path: ' . $url);
    }

    $entity = $this->entityTypeManager->getStorage($entity_type)
      ->load($entity_id);
    $event = EntityActionEvent::createFromEntity($entity, EntityActionEventInterface::DELETE_ACTION);
    $this->eventDispatcher->dispatch($event, EntityEvents::ENTITY_ACTION);

    $this->io()->write('Invalidated path: ' . $url . PHP_EOL);
  }

}
