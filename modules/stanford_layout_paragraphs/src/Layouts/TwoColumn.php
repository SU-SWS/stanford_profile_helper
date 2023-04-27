<?php

namespace Drupal\stanford_layout_paragraphs\Layouts;

use Drupal\layout_builder\Plugin\Layout\MultiWidthLayoutBase;

/**
 * Two column layout class.
 */
class TwoColumn extends MultiWidthLayoutBase {

  /**
   * {@inheritDoc}
   */
  protected function getWidthOptions() {
    return [
      '50-50' => '50% - 50%',
      '33-67' => '33% - 67%',
      '67-33' => '67% - 33%',
    ];
  }

}
