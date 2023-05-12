<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\Plugin\Filter;

use Drupal\Core\State\StateInterface;
use Drupal\stanford_profile_helper\Plugin\Filter\SuCleanHtml;
use Drupal\Tests\UnitTestCase;

/**
 * Class SulCleanHtmlTest.
 */
class SuCleanHtmlTest extends UnitTestCase {

  /**
   * Test the clean html filter.
   */
  public function testFilter() {
    $config = [];
    $definition = ['provider' => 'stanford_profile_helper'];
    $state = $this->createMock(StateInterface::class);
    $state->method('get')->willReturn(TRUE);

    $filter = new SuCleanHtml($config, '', $definition, $state);
    $result = $filter->process("\r\n<!-- FOO BAR BAZ-->\n\n<div>foo</div>\n\n\n<div>\r\nbar\r\n\r\nbaz</div>\r\n", NULL);

    $this->assertEquals('<div>foo</div><div> bar baz</div>', (string) $result);
  }

}
