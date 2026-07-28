<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\Hook;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\State\StateInterface;
use Drupal\stanford_decoupled\Config\DecoupledConfigOverrides;

/**
 * Hooks that run during cron for the decoupled module.
 */
class CronHooks {

  /**
   * Hook constructor.
   *
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager service.
   */
  public function __construct(protected StateInterface $state, protected EntityTypeManagerInterface $entityTypeManager) {}

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    if (!DecoupledConfigOverrides::isDecoupled()) {
      return;
    }
    $last_run = $this->state->get('stanford-decoupled-last-ran', 0);
    $now = time();
    $node_storage = $this->entityTypeManager->getStorage('node');
    $query = $node_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', TRUE);

    $conditions = $query->orConditionGroup();

    $start_conditions = $query->andConditionGroup();
    $end_conditions = $query->andConditionGroup();

    $start_conditions->condition('su_event_date_time.value', $last_run, '>=');
    $start_conditions->condition('su_event_date_time.value', $now, '<=');

    $end_conditions->condition('su_event_date_time.end_value', $last_run, '>=');
    $end_conditions->condition('su_event_date_time.end_value', $now, '<=');

    $conditions->condition($start_conditions);
    $conditions->condition($end_conditions);

    $query->condition($conditions);
    $results = $query->execute();

    foreach ($node_storage->loadMultiple($results) as $node) {
      next_entity_update($node);
    }
    $this->state->set('stanford-decoupled-last-ran', $now);
  }

}
