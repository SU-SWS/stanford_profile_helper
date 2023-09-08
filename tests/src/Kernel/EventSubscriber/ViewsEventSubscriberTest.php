<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;
use Drupal\views\Entity\View;
use Drupal\views\Views;

/**
 * Test the event subscriber.
 *
 * @coversDefaultClass \Drupal\stanford_profile_helper\EventSubscriber\ViewsEventSubscriber
 */
class ViewsEventSubscriberTest extends SuProfileHelperKernelTestBase {

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'config_pages',
    'core_event_dispatcher',
    'hook_event_dispatcher',
    'preprocess_event_dispatcher',
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
    'link',
    'redirect',
    'text',
    'field',
    'config_pages',
    'link',
    'views',
    'views_event_dispatcher',
    'views_custom_cache_tag',
  ];

  public function testCacheTags() {
    $view_id = $this->randomMachineName();
    $view = View::create([
      'id' => $view_id,
      'base_table' => 'node_field_data',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'id' => 'default',
          'display_options' => [
            'fields' => [
              'nid' => [
                'table' => 'node_field_data',
                'field' => 'nid',
                'id' => 'nid',
                'plugin_id' => 'field',
              ],
            ],
          ],
        ],
        'test_block' => [
          'display_plugin' => 'block',
          'id' => 'test_block',
          'display_options' => [
            'filters' => [
              'type' => [
                'table' => 'node_field_data',
                'field' => 'type',
                'id' => 'type',
                'entity_type' => 'node',
                'entity_field' => 'type',
                'plugin_id' => 'bundle',
                'value' => [
                  'stanford_event' => 'stanford_event',
                ],
              ],
            ],
          ],
        ],
      ],
    ]);
    $view->save();

    $executable = Views::getView($view_id);
    $build = $executable->preview();
    $this->assertEquals('tag', $build['#view']->getDisplay()->options['cache']['type']);

    $executable = Views::getView($view_id);
    $build = $executable->preview('test_block');
    $this->assertEquals('custom_tag', $build['#view']->getDisplay()->options['cache']['type']);
    $this->assertContains('node_list:stanford_event', $build['#view']->getDisplay()->options['cache']['options']);
  }

}
