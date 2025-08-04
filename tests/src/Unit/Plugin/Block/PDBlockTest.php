<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\Plugin\Block;

use Drupal\Component\Uuid\UuidInterface;
use Drupal\stanford_profile_helper\Plugin\Block\PDBlock;
use Drupal\Tests\UnitTestCase;

class PDBlockTest extends UnitTestCase {

  public function testBuild() {
    $uuidService = $this->createMock(UuidInterface::class);
    $pdb_info = [
      'machine_name' => 'foo',
    ];
    $block = new PDBlock([], '', [
      'provider' => 'stanford_profile_helper',
      'info' => $pdb_info,
    ], $uuidService);
    $build = $block->build();
    $this->assertEquals(['foo'], $build['#allowed_tags']);
    $this->assertEquals(['library' => []], $block->attachLibraries($pdb_info));
  }

}
