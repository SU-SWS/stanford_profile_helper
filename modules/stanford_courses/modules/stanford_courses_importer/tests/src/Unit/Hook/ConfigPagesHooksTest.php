<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_courses_importer\Unit\Hook;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\config_pages\ConfigPagesInterface;
use Drupal\migrate\Plugin\MigrationPluginManager;
use Drupal\stanford_courses_importer\Hook\ConfigPagesHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for ConfigPagesHooks.
 */
#[Group('stanford_courses_importer')]
class ConfigPagesHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_courses_importer\Hook\ConfigPagesHooks
   */
  protected ConfigPagesHooks $hooks;

  /**
   * The mocked migration plugin manager.
   *
   * @var \Drupal\migrate\Plugin\MigrationPluginManager|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $migrationManager;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new ConfigPagesHooks();

    $container = new ContainerBuilder();

    $this->migrationManager = $this->createMock(MigrationPluginManager::class);
    $container->set('plugin.manager.migration', $this->migrationManager);

    \Drupal::setContainer($container);
  }

  /**
   * Entity bundle other than 'stanford_courses_importer' — no cache clear.
   */
  public function testConfigPagesPresaveOtherBundle() {
    $entity = $this->createMock(ConfigPagesInterface::class);
    $entity->method('bundle')->willReturn('some_other_bundle');

    $this->migrationManager->expects($this->never())->method('clearCachedDefinitions');

    $this->hooks->configPagesPresave($entity);
  }

  /**
   * Entity bundle matches 'stanford_courses_importer' — cache is cleared.
   */
  public function testConfigPagesPresaveMatchingBundle() {
    $entity = $this->createMock(ConfigPagesInterface::class);
    $entity->method('bundle')->willReturn('stanford_courses_importer');

    $this->migrationManager->expects($this->once())->method('clearCachedDefinitions');

    $this->hooks->configPagesPresave($entity);
  }

}
