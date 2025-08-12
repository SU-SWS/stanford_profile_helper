<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Hooks that are at the page level.
 */
class PageHooks {

  /**
   * Page hook constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current active user.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(protected AccountProxyInterface $currentUser, protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Add drupal settings of the current logged in user to the page.
   *
   * @param array $attachments
   *   Page attachments
   */
  #[Hook('page_attachments')]
  public function pageAttachments(array &$attachments): void {
    $env = getenv('AH_SITE_ENVIRONMENT');

    // Add SiteImprove analytics for anonymous users on prod sites.
    // ACE prod is 'prod'; ACSF can be '01live', '02live', ...
    if ($this->currentUser->isAnonymous()) {
      if ($env && ($env === 'prod' || preg_match('/^\d*live$/', $env))) {
        $attachments['#attached']['library'][] = 'stanford_profile_helper/siteimprove.analytics';
      }
      return;
    }

    /** @var \Drupal\user\UserInterface $user_entity */
    $user_entity = $this->entityTypeManager->getStorage('user')
      ->load($this->currentUser->id());

    $displayName = $user_entity->hasField('su_display_name') ?
      $user_entity->get('su_display_name')->getString() :
      $this->currentUser->getAccountName();
    $attachments['#attached']['drupalSettings']['user'] = [
      'email' => $this->currentUser->getEmail(),
      'displayName' => $displayName,
      'name' => $this->currentUser->getAccountName(),
    ];
  }

}
