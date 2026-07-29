<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

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

}
