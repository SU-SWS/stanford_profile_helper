<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;

/**
 * Test the event subscriber.
 *
 */
abstract class SuProfileHelperKernelTestBase extends KernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'config_pages',
    'default_content',
    'node',
    'serialization',
    'stanford_profile_helper',
    'system',
    'user',
    'path_alias',
    'rabbit_hole',
    'rh_node',
    'menu_link_content',
    'redirect',
    'text',
    'field',
    'field_ui',
    'config_pages',
    'link',
    'taxonomy',
    'pathauto',
    'token',
    'options',
    'file',
    'next',
    'menu_link',
    'google_analytics',
    'viewfield',
    'views',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->container->get('module_installer')->install(['paragraphs']);
    $site_settings = Settings::getAll();
    $site_settings['STANFORD_PROFILE_HELPER_DISABLE_NEXT'] = TRUE;
    new Settings($site_settings);

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');
    $this->installEntitySchema('path_alias');
    $this->installEntitySchema('menu_link_content');
    $this->installEntitySchema('redirect');
    $this->installEntitySchema('field_storage_config');
    $this->installEntitySchema('config_pages');
    $this->installEntitySchema('taxonomy_term');
    $this->installSchema('node', ['node_access']);
    $this->installConfig('system');
    $this->setInstallProfile('test_stanford_profile_helper');

    NodeType::create(['type' => 'stanford_event', 'name' => 'Event'])->save();
  }

}
