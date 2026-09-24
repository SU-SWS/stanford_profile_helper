<?php

namespace Drupal\stanford_profile_drush\Drush\Commands;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\AutowireTrait;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;

/**
 * Stanford Profile Drush commands.
 */
class StanfordProfileCommands extends DrushCommands {

  use AutowireTrait;

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
    protected EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * Unpublish the node that is set as the site's front page.
   */
  #[CLI\Command(name: 'stanford-profile:unpublish-homepage', aliases: ['su:unpublish-home'])]
  public function unpublishHomepage(): void {
    $homepage = $this->configFactory->get('system.site')->get('page.front');
    $nid = (int) str_replace('/node/', '', (string) $homepage);
    if ($nid) {
      $this->entityTypeManager->getStorage('node')
        ->load($nid)
        ?->setUnpublished()
        ->save();
    }
  }

}
