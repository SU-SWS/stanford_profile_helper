<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\node\Entity\Node;
use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;
use Drupal\user\Entity\Role;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test the event subscriber.
 */
#[RunTestsInSeparateProcesses]
class NodeHooksTest extends SuProfileHelperKernelTestBase {

  /**
   * Entity Pre-save event listener.
   */
  public function testNodePresave() {
    $role = Role::create(['id' => 'foo', 'label' => 'Foo']);
    $role->save();

    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
    $this->assertEmpty($nodes);
    $node = Node::create(['type' => 'stanford_event', 'title' => 'Foo Bar']);
    $node->save();
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
    $this->assertCount(2, $nodes);

    \Drupal::state()
      ->delete('stanford_profile_helper.default_content.stanford_event');
    $node = Node::create(['type' => 'stanford_event', 'title' => 'Bar Foo']);
    $node->save();
    $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadMultiple();
    $this->assertCount(3, $nodes);
  }

  /**
   * Pre-save alters the metatags based on the search exclusion field.
   */
  public function testNodePresaveAltersMetatags() {
    FieldStorageConfig::create([
      'field_name' => 'su_search_exclusion',
      'entity_type' => 'node',
      'type' => 'boolean',
    ])->save();
    FieldConfig::create([
      'field_name' => 'su_search_exclusion',
      'entity_type' => 'node',
      'bundle' => 'stanford_event',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'su_metatags',
      'entity_type' => 'node',
      'type' => 'string_long',
    ])->save();
    FieldConfig::create([
      'field_name' => 'su_metatags',
      'entity_type' => 'node',
      'bundle' => 'stanford_event',
    ])->save();

    // With exclusion enabled, robots noindex/nofollow is added.
    $node = Node::create([
      'type' => 'stanford_event',
      'title' => 'Foo',
      'su_search_exclusion' => TRUE,
    ]);
    $node->save();

    $tags = json_decode($node->get('su_metatags')->getString(), TRUE);
    $this->assertEquals('noindex, nofollow', $tags['robots']);

    // Disabling exclusion removes the robots tag again.
    $node->set('su_search_exclusion', FALSE);
    $node->save();

    $tags = json_decode($node->get('su_metatags')->getString(), TRUE);
    $this->assertArrayNotHasKey('robots', $tags ?? []);
  }

}
