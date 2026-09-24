<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\Plugin\Block;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\node\NodeInterface;
use Drupal\rabbit_hole\BehaviorInvokerInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginInterface;
use Drupal\rabbit_hole\Plugin\RabbitHoleBehaviorPluginManager;
use Drupal\stanford_profile_helper\Plugin\Block\RabbitHoleMessageBlock;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test rabbit hole message block.
 */
#[Group('stanford_profile_helper')]
#[RunTestsInSeparateProcesses]
class RabbitHoleMessageBlockTest extends KernelTestBase {

  /**
   * @var \Drupal\stanford_profile_helper\Plugin\Block\RabbitHoleMessageBlock
   */
  protected $block;

  /**
   * Node the block is displayed for.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected NodeInterface $node;

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
    $this->node = Node::create(['type' => 'page', 'title' => 'foo']);
    $this->node->save();
    $node = $this->node;

    /** @var \Drupal\Core\Block\BlockManager $block_manager */
    $block_manager = $this->container->get('plugin.manager.block');
    $this->block = $block_manager->createInstance('rabbit_hole_message');
    $this->block->setContextValue('node', $node);
  }

  /**
   * The block is hidden from users who can't bypass rabbit hole.
   */
  public function testAccess() {
    $account = $this->createMock(AccountProxyInterface::class);
    $this->assertFalse($this->block->access($account));
  }

  /**
   * The block shows on the node's own page to users who can bypass it.
   */
  public function testAccessOnNodePage() {
    $this->container->get('path.current')->setPath('/node/' . $this->node->id());
    $block = $this->getBlock([]);

    $account = $this->createMock(AccountProxyInterface::class);
    $account->method('hasPermission')
      ->willReturnMap([['rabbit hole bypass node', TRUE]]);
    $this->assertTrue($block->access($account));

    $this->container->get('path.current')->setPath('/node/999');
    $this->assertFalse($block->access($account));
  }

  /**
   * Nodes without a rabbit hole redirect display nothing.
   */
  public function testBuild() {
    $build = $this->block->build();
    $this->assertEmpty($build);

    $this->assertEmpty($this->getBlock(['rh_action' => 'display_page'])->build());
    $this->assertEmpty($this->getBlock(['rh_action' => 'page_redirect'], new Response())->build());
  }

  /**
   * Redirects display a message with the absolute destination.
   */
  public function testBuildRedirect() {
    $block = $this->getBlock(['rh_action' => 'page_redirect'], new TrustedRedirectResponse('/foo/bar'));
    $build = $block->build();
    $this->assertEquals('rabbit_hole_message', $build['#theme']);
    $this->assertStringEndsWith('/foo/bar', $build['#destination']);
    $this->assertStringStartsWith('http', $build['#destination']);

    $block = $this->getBlock(['rh_action' => 'page_redirect'], new TrustedRedirectResponse('https://www.stanford.edu/foo'));
    $this->assertEquals('https://www.stanford.edu/foo', $block->build()['#destination']);
  }

  /**
   * Create the block with mocked rabbit hole services.
   *
   * @param array $values
   *   Rabbit hole values for the node.
   * @param \Symfony\Component\HttpFoundation\Response|null $response
   *   Response returned by the rabbit hole behavior plugin.
   *
   * @return \Drupal\stanford_profile_helper\Plugin\Block\RabbitHoleMessageBlock
   *   Block plugin.
   */
  protected function getBlock(array $values, ?Response $response = NULL): RabbitHoleMessageBlock {
    $behavior_invoker = $this->createMock(BehaviorInvokerInterface::class);
    $behavior_invoker->method('getRabbitHoleValuesForEntity')->willReturn($values);

    $plugin = $this->createMock(RabbitHoleBehaviorPluginInterface::class);
    $plugin->method('performAction')->willReturn($response);
    $plugin_manager = $this->createMock(RabbitHoleBehaviorPluginManager::class);
    $plugin_manager->method('createInstance')
      ->with('page_redirect', $values)
      ->willReturn($plugin);

    $definition = $this->container->get('plugin.manager.block')
      ->getDefinition('rabbit_hole_message');
    $block = new RabbitHoleMessageBlock([], 'rabbit_hole_message', $definition, $this->container->get('path.current'), $behavior_invoker, $plugin_manager);
    $block->setContextValue('node', $this->node);
    return $block;
  }

}
