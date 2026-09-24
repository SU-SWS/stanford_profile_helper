<?php

namespace Drupal\Tests\stanford_profile_drush\Unit\Drush\Commands;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_profile_drush\Drush\Commands\StanfordProfileCommands;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the Stanford Profile drush commands.
 */
#[Group('stanford_profile_drush')]
class StanfordProfileCommandsTest extends UnitTestCase {

  /**
   * The homepage node is unpublished.
   */
  public function testUnpublishHomepage() {
    $node = $this->createMock(NodeInterface::class);
    $node->expects($this->once())->method('setUnpublished')->willReturnSelf();
    $node->expects($this->once())->method('save');

    $this->getCommands('/node/12', 12, $node)->unpublishHomepage();
  }

  /**
   * Nothing happens when the front page isn't a node.
   */
  public function testUnpublishHomepageNotNode() {
    $this->getCommands('/user/login', NULL, NULL)->unpublishHomepage();
  }

  /**
   * A front page node that no longer exists doesn't cause an error.
   */
  public function testUnpublishHomepageMissingNode() {
    $this->getCommands('/node/99', 99, NULL)->unpublishHomepage();
  }

  /**
   * Get the drush commands object.
   *
   * @param string $front_page
   *   The site's front page path.
   * @param int|null $nid
   *   Node id expected to be loaded, or NULL if no load should happen.
   * @param \Drupal\node\NodeInterface|null $node
   *   Node returned from storage.
   *
   * @return \Drupal\stanford_profile_drush\Drush\Commands\StanfordProfileCommands
   *   Commands object.
   */
  protected function getCommands(string $front_page, ?int $nid, ?NodeInterface $node): StanfordProfileCommands {
    $config_factory = $this->getConfigFactoryStub([
      'system.site' => ['page.front' => $front_page],
    ]);

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($nid ? $this->once() : $this->never())
      ->method('load')
      ->with($nid)
      ->willReturn($node);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    return new StanfordProfileCommands($config_factory, $entity_type_manager);
  }

}
