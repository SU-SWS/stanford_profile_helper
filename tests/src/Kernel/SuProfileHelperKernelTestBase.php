<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel;

use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\stanford_profile_helper\StanfordDefaultContentInterface;

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
    'paragraphs',
    'options',
    'file',
    'next',
    'menu_link',
    'google_analytics',
  ];

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
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

//    $entity = $this->createMock(NodeInterface::class);
//    $entity->method('label')->willReturn('Foo Bar');
//    $entity->method('toUrl')->willReturn(new Url('node.canonoical', ['node' => $entity]));
//
//    $default_content_mock = $this->createMock(StanfordDefaultContentInterface::class);
//    $default_content_mock->method('createDefaultContent')
//      ->willReturnReference($entity);
//
//    $container = \Drupal::getContainer();
//    $container->set('stanford_profile_helper.default_content', $default_content_mock);
//    \Drupal::setContainer($container);
  }

}
