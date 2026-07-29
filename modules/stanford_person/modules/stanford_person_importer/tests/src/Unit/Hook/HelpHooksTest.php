<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_person_importer\Unit\Hook;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\stanford_person_importer\Hook\HelpHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for HelpHooks.
 */
#[Group('stanford_person_importer')]
#[CoversClass(HelpHooks::class)]
class HelpHooksTest extends UnitTestCase {

  /**
   * The hooks class under test.
   *
   * @var \Drupal\stanford_person_importer\Hook\HelpHooks
   */
  protected HelpHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new HelpHooks();

    $container = new ContainerBuilder();
    $container->set('string_translation', $this->getStringTranslationStub());
    \Drupal::setContainer($container);
  }

  /**
   * The module help route returns the about text.
   */
  public function testHelpPageStanfordPersonImporter() {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $output = $this->hooks->help('help.page.stanford_person_importer', $route_match);

    $this->assertStringContainsString('About', $output);
    $this->assertStringContainsString('Migration support for importing of profile information from stanford.edu.', $output);
  }

  /**
   * Any other route returns nothing.
   */
  public function testHelpOtherRoute() {
    $route_match = $this->createMock(RouteMatchInterface::class);
    $output = $this->hooks->help('help.page.some_other_module', $route_match);

    $this->assertNull($output);
  }

}
