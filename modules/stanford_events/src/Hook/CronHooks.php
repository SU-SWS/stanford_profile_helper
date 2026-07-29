<?php

declare(strict_types=1);

namespace Drupal\stanford_events\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;

/**
 * Hooks that run during cron for stanford_events.
 */
class CronHooks {

  /**
   * Cron hook constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   */
  public function __construct(protected EntityTypeManagerInterface $entityTypeManager, protected StateInterface $state) {}

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {

    // Find all events that have passed since last cron run date and now.
    // Clear cache for those particular events and clear out the views cache.
    $last_cron = $this->state->get('system.cron_last');
    $now = time();
    $query = $this->entityTypeManager->getStorage('node')->getQuery();
    $query->accessCheck(FALSE);
    // Conditions.
    $query->condition('type', 'stanford_event');
    $query->condition('status', 1);
    $query->condition('su_event_date_time.end_value', $last_cron, ">=");
    $query->condition('su_event_date_time.end_value', $now, "<=");

    // Fetch.
    $entity_ids = $query->execute();
    $tags = ["node_list:stanford_event"];

    if (count($entity_ids)) {

      // Create an array of "node:{id}".
      array_walk(
        $entity_ids,
        function (&$item, $key, $prefix) {
          $item = $prefix . $item;
        },
        "node:"
      );

      // Clear all the things.
      $tags = array_merge($tags, $entity_ids);
      Cache::invalidateTags($tags);
    }

  }

}
