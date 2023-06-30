<?php

namespace Drupal\stanford_layout_paragraphs\Controller;

use Drupal\layout_paragraphs\Controller\ChooseComponentController;
use Drupal\layout_paragraphs\LayoutParagraphsLayout;
use Symfony\Component\HttpFoundation\Request;

/**
 * Overrides and adds to the LP choose component controller.
 *
 * @codeCoverageIgnore Unable to test the controller, rely on acceptance tests.
 */
class SuChooseComponentController extends ChooseComponentController {

  /**
   * {@inheritDoc}
   */
  public function list(Request $request, LayoutParagraphsLayout $layout_paragraphs_layout) {
    $response = parent::list($request, $layout_paragraphs_layout);
    if (is_array($response) && isset($response['#title'])) {
      $response['#title'] = $this->t('Choose a paragraph');
    }
    return $response;
  }

}
