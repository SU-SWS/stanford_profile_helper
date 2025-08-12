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
    if ($this->currentUser->isAnonymous()) {
      return;
    }

    $user_entity = $this->entityTypeManager->getStorage('user')
      ->load($this->currentUser->id());
    $attachments['#attached']['drupalSettings']['user'] = [
      'email' => $this->currentUser->getEmail(),
      'displayName' => $user_entity->get('su_display_name')->getString(),
      'name' => $this->currentUser->getAccountName(),
    ];
  }

}
