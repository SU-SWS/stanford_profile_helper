<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\EventSubscriber;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Url;
use Drupal\next\Entity\NextSiteInterface;
use Drupal\next\Event\EntityActionEvent;
use Drupal\next\Event\EntityEvents;
use Drupal\stanford_profile_helper\Event\MenuCacheEvent;
use GuzzleHttp\ClientInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Event subscriber for events on decoupled sites.
 *
 * @codeCoverageIgnore
 */
final class DecoupledEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents(): array {
    return [
      MenuCacheEvent::CACHE_CLEARED => ['onMenuCacheClear'],
      EntityEvents::ENTITY_ACTION => ['onNextEntityAction', 10],
      KernelEvents::TERMINATE => ['onKernelTerminate'],
    ];
  }

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected StateInterface $state,
    protected ClientInterface $client,
    protected Connection $database
  ) {}

  /**
   * At the end of the execution, query the table to revalidate any paths.
   *
   * @param \Symfony\Component\HttpKernel\Event\TerminateEvent $event
   *   Termination event.
   */
  public function onKernelTerminate(TerminateEvent $event): void {
    $query = $this->database->select('stanford_decoupled_revalidation', 's')
      ->fields('s')
      ->execute();
    $revalidations = [];
    while ($row = $query->fetchAssoc()) {
      $revalidations[$row['site']][] = $row['path'];
    }

    foreach ($revalidations as $siteId => $paths) {
      /** @var \Drupal\next\Entity\NextSiteInterface $site */
      $site = $this->entityTypeManager->getStorage('next_site')->load($siteId);
      if (!$site) {
        continue;
      }

      try {
        $this->revalidatePaths($site, $paths);
        $this->database->delete('stanford_decoupled_revalidation')
          ->condition('site', $site->id())
          ->condition('path', $paths, 'IN')
          ->execute();
      }
      catch (\Exception $e) {
        // Log it.
      }
    }
  }

  /**
   * Revalidate paths that were stored in the database.
   *
   * @param \Drupal\next\Entity\NextSiteInterface $site
   *   Next site entity.
   * @param array $paths
   *   List of paths or tags to revalidate.
   */
  protected function revalidatePaths(NextSiteInterface $site, array $paths): void {
    $secret = $site->getRevalidateSecret();
    $revalidate_url = Url::fromUri($site->getRevalidateUrl());

    if (!$revalidate_url) {
      throw new \Exception('No revalidate url set.');
    }

    asort($paths);
    $this->client->request('POST', $revalidate_url->toString(), [
      'headers' => ['Authorization' => "Bearer $secret"],
      'json' => ['paths' => array_values(array_unique($paths))],
    ]);
  }

  /**
   * Stop propagation of the event if on local environment and CLI execution.
   *
   * @param \Drupal\next\Event\EntityActionEvent $event
   *   Next module event.
   */
  public function onNextEntityAction(EntityActionEvent $event) {
    if ($this->state->get('stanford_decoupled.stop_propagation', FALSE)) {
      $event->stopPropagation();
    }

    // When the site is not on an Acquia environment and running via the CLI, we
    // don't need to do any invalidations. This is often for migration runs.
    if (!getenv('AH_SITE_ENVIRONMENT') && !getenv('PANTHEON_ENVIRONMENT') && PHP_SAPI == 'cli') {
      $event->stopPropagation();
    }
  }

  /**
   * Invalidate next menu caches after the drupal menus cache is cleared.
   *
   * @param \Drupal\stanford_profile_helper\Event\MenuCacheEvent $event
   *   Triggered event.
   */
  public function onMenuCacheClear(MenuCacheEvent $event) {
    $fake_menu_link = $this->entityTypeManager->getStorage('menu_link_content')
      ->create(['id' => 'id']);
    next_entity_insert($fake_menu_link);
  }

}
