<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileExists;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StreamWrapper\StreamWrapperManagerInterface;

/**
 * Hooks that run during cron.
 */
class CronHooks {

  /**
   * Cron hook constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\StreamWrapper\StreamWrapperManagerInterface $streamWrapperManager
   *   Stream wrapper manager service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   File system service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
    protected StreamWrapperManagerInterface $streamWrapperManager,
    protected FileSystemInterface $fileSystem,
  ) {}

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    if (!\Drupal::hasService('config_pages.loader')) {
      return;
    }
    /** @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface $config_pages */
    $config_pages = \Drupal::service('config_pages.loader');

    $ally = $config_pages->getValue('stanford_basic_site_settings', 'su_site_a11y_contact', [], 'value');
    $canonical_url = $config_pages->getValue('stanford_basic_site_settings', 'su_site_url', 0, 'uri');
    $created = (int) $config_pages->getValue('stanford_basic_site_settings', 'su_site_created', 0, 'value');
    $org_ids = $config_pages->getValue('stanford_basic_site_settings', 'su_site_org', [], 'target_id');
    $owners = $config_pages->getValue('stanford_basic_site_settings', 'su_site_owner_contact', [], 'value');
    $renewal_date = $config_pages->getValue('stanford_basic_site_settings', 'su_site_renewal_due', 0, 'value');
    $site_managers = $config_pages->getValue('stanford_basic_site_settings', 'su_site_tech_contact', [], 'value');
    $site_type = $config_pages->getValue('stanford_basic_site_settings', 'su_site_type', 0, 'value');
    $person_sunet = $config_pages->getValue('stanford_basic_site_settings', 'su_site_sunetid', 0, 'value');

    $orgs = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadMultiple($org_ids);

    foreach ($orgs as &$org) {
      $org = $org->label();
    }

    $user_storage = $this->entityTypeManager->getStorage('user');
    $uid = $user_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('roles', ['site_manager', 'contributor', 'site_editor'], 'IN')
      ->sort('access', 'DESC')
      ->range(0, 1)
      ->execute();
    $last_editor = $uid ? $user_storage->load(reset($uid)) : NULL;

    $site_information = [
      'accessibility' => $ally ?: [],
      'canonicalUrl' => $canonical_url,
      'created' => $created,
      'organizations' => array_values($orgs) ?: [],
      'owners' => $owners ?: [],
      'renewalDate' => $renewal_date,
      'siteManagers' => $site_managers ?: [],
      'siteName' => $this->configFactory->get('system.site')->get('name'),
      'siteType' => $site_type,
      'theme' => $this->configFactory->get('system.theme')->get('default'),
      'personSunet' => $person_sunet,
      'lastEditorAccess' => (int) $last_editor?->get('access')->getString(),
    ];
    $uri = 'private://stanford';
    $directory = $this->streamWrapperManager->normalizeUri($uri);
    $this->fileSystem->prepareDirectory($directory, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $this->fileSystem->saveData(json_encode($site_information, JSON_PRETTY_PRINT), "$directory/site-info.json", FileExists::Replace);
  }

}
