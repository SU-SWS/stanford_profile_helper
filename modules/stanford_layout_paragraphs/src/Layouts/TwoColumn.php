<?php

namespace Drupal\stanford_layout_paragraphs\Layouts;

use Drupal\layout_builder\Plugin\Layout\MultiWidthLayoutBase;

/**
 * Two column layout class.
 */
class TwoColumn extends MultiWidthLayoutBase {

  /**
   * {@inheritDoc}
   *
   * @codeCoverageIgnore Nothing to test.
   */
  protected function getWidthOptions() {
    return [
      '50-50' => 'Equal Columns',
      '33-67' => 'Larger Right Column',
      '67-33' => 'Larger Left Column',
    ];
  }

}
