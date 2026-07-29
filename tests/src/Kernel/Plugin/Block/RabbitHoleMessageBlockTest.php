<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\Plugin\Block;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Test rabbit hole message block.
 */
#[RunTestsInSeparateProcesses]
class RabbitHoleMessageBlockTest extends KernelTestBase {

  /**
   * @var \Drupal\stanford_profile_helper\Plugin\Block\RabbitHoleMessageBlock
   */
  protected $block;

  /**
   * {@inheritDoc}
   */
  protected static $modules = [
    'node',
    'stanford_profile_helper',
    'file',
    'system',
    'user',
    'rabbit_hole',
    'rh_node',
    'config_pages',
    'pdb',
  ];

  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('node');
    $this->installEntitySchema('user');

    NodeType::create(['type' => 'page', 'name' => 'page'])->save();
    $node = Node::create(['type' => 'page', 'title' => 'foo']);
    $node->save();

    /** @var \Drupal\Core\Block\BlockManager $block_manager */
    $block_manager = $this->container->get('plugin.manager.block');
    $this->block = $block_manager->createInstance('rabbit_hole_message');
    $this->block->setContextValue('node', $node);
  }

  public function testAccess() {
    $account = $this->createMock(AccountProxyInterface::class);
    $this->assertFalse($this->block->access($account));
  }

    public function testBuild() {
      $build = $this->block->build();
      $this->assertEmpty($build);
    }

}

