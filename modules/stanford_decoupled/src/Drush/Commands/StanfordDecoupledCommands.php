<?php

namespace Drupal\stanford_decoupled\Drush\Commands;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
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
  public function __construct(private readonly EntityTypeManagerInterface $entityTypeManager, private readonly UuidInterface $uuid, private readonly PasswordGeneratorInterface $passwordGenerator) {}

  /**
   * Create a next site entity to connect with NextJS site
   */
  #[CLI\Command(name: 'stanford-decoupled:connect-next', aliases: ['su-next-connect'])]
  #[CLI\Argument(name: 'domain', description: 'Next.js Domain.')]
  #[CLI\Option(name: 'preview-secret', description: 'Use a specific preview secret')]
  #[CLI\Option(name: 'revalidation-secret', description: 'Use a specific revalidation secret')]
  #[CLI\Option(name: 'format', description: 'Format the result data. Available formats: json,string')]
  #[CLI\Usage(name: 'stanford-decoupled:connect-next "https://localhost:3000"', description: 'Create a next site entity to connect with Next.JS site')]
  public function connectNextSite($domain = 'https://localhost:3000', $options = [
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
    $nextjs_users = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => 'nextjs']);
    if(!$nextjs_users){
      $nextjs_users = [$this->entityTypeManager->getStorage('user')->create(['name' => 'nextjs'])];
    }
    /** @var \Drupal\user\Entity\User $nextjs_user */
    $nextjs_user = reset($nextjs_users);
    $nextjs_user->addRole('decoupled_site_users')
      ->setPassword($nextjs_pass)
      ->save();

    $nextjs_admin_pass = $this->passwordGenerator->generate(20);
    $nextjs_admin_users = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => 'nextjsAdmin']);
    if(!$nextjs_admin_users){
      $nextjs_admin_users = [$this->entityTypeManager->getStorage('user')->create(['name' => 'nextjsAdmin'])];
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
    foreach($output as $key => $value){
      $lined_output[] = "$key=$value";
    }
    $this->output()->write(implode(PHP_EOL, $lined_output).PHP_EOL);
  }

}
