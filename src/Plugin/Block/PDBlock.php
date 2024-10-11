<?php

namespace Drupal\stanford_profile_helper\Plugin\Block;

use Drupal\Core\Block\Attribute\Block;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\pdb\Plugin\Block\PdbBlock;
use Drupal\stanford_profile_helper\Plugin\Derivative\ReactBlockDeriver;

/**
 * Exposes a React component as a block.
 */
#[Block(
  id: "pdb_component",
  admin_label: new TranslatableMarkup("PDB Component"),
  deriver: ReactBlockDeriver::class
)]
class PDBlock extends PdbBlock {

  /**
   * {@inheritDoc}
   *
   * @codeCoverageIgnore
   */
  public function attachLibraries(array $component) {
    return ['library' => parent::attachLibraries($component)];
  }

}
