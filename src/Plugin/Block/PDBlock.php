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
  deriver: ReactBlockDeriver::class,
  category: new TranslatableMarkup("PDB Components")
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

  /**
   * {@inheritdoc}
   */
  public function build() {
    $info = $this->getComponentInfo();
    $machine_name = $info['machine_name'];

    $build = parent::build();
    $build['#allowed_tags'] = [$machine_name];
    $build['#markup'] = '<' . $machine_name . ' id="' . $machine_name . '"></' . $machine_name . '>';

    return $build;
  }

}
