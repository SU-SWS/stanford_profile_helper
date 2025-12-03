<?php

namespace Drupal\stanford_profile_drush\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;
use Drush\Attributes as CLI;

/**
 * Class StanfordProfileCommands.
 *
 * @package Drupal\stanford_profile_drush\Commands
 * @codeCoverageIgnore
 */
class StanfordProfileCommands extends DrushCommands {

  /**
   * Drush command constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(
    protected ConfigFactoryInterface $configFactory,
    protected EntityTypeManagerInterface $entityTypeManager
  ) {}

  #[CLI\Command(name: 'stanford-profile:unpublish-homepage', aliases: ['su:unpublish-home'])]
  public function unpublishHomepage() {
    $homepage = $this->configFactory->get('system.site')->get('page.front');
    $nid = (int) str_replace('/node/', '', $homepage);
    if ($nid) {
      $this->entityTypeManager->getStorage('node')
        ->load($nid)
        ->setUnpublished()
        ->save();
    }
  }

}
