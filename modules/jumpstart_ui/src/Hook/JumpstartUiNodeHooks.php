<?php

declare(strict_types=1);

namespace Drupal\jumpstart_ui\Hook;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\node\NodeInterface;
use function Symfony\Component\String\u;

class JumpstartUiNodeHooks {

  /**
   * Implements hook_ENTITY_TYPE_view().
   */
  #[Hook('node_view')]
  public function nodeView(array &$build, NodeInterface $node, EntityViewDisplayInterface $display, $view_mode) {
    $bundle = u($node->bundle())->camel()->toString();
    $method = "{$bundle}View";
    if (method_exists($this, $method)) {
      $this->$method($build, $node, $view_mode);
    }
  }

  protected function stanfordEventView(&$build, NodeInterface $node, string $view_mode) {
    $date = $node->get('su_event_date_time')->get(0)->getValue();
    [$startMonth, $startDay] = explode(' ', date('M j', (int) $date['value']));
    [$endMonth, $endDay] = explode(' ', date('M j', (int) $date['end_value']));
    dpm($build);
    $build = [
      'contents' => ['#access' => FALSE, ...$build],
      'component' => [
        '#type' => 'component',
        '#component' => 'jumpstart_ui:event_card',
        '#props' => [
          'header_tag' => $view_mode == 'stanford_h3_card' ? 'h3' : 'h2',
        ],
        '#slots' => [
          'start_month' => $startMonth,
          'start_date' => $startDay,
          'end_month' => $endMonth,
          'end_date' => $endDay,
          'event_type' => $build['su_event_type'] ?? NULL,
          'headline' => $build['title'] ?? NULL,
          'subheadline' => $build['su_event_subheadline'] ?? NULL,
          'url' => $node->get('su_event_source')->getString() ?: $node->toUrl()->toString(),
          'date_time' => $build['su_event_date_time'] ?? NULL,
          'location' => $build['su_event_alt_loc'] ?? NULL,
          'address' => $build['su_event_location'] ?? NULL,
        ],
      ],
    ];
  }

  protected function stanfordEventSeriesView(&$build, NodeInterface $node, string $view_mode) {}

  protected function stanfordMediaView(&$build, NodeInterface $node, string $view_mode) {}

  protected function stanfordNewsView(&$build, NodeInterface $node, string $view_mode) {}

  protected function stanfordOpportunityView(&$build, NodeInterface $node, string $view_mode) {}

  protected function stanfordPageView(&$build, NodeInterface $node, string $view_mode) {}

  protected function stanfordPersonView(&$build, NodeInterface $node, string $view_mode) {}

  protected function stanfordPolicyView(&$build, NodeInterface $node, string $view_mode) {}

  protected function stanfordPublicationView(&$build, NodeInterface $node, string $view_mode) {}

}
