<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\Plugin\GraphQL\DataProducer;

use Drupal\Core\Plugin\Context\ContextDefinition;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\graphql\Attribute\DataProducer;
use Drupal\graphql\Plugin\GraphQL\DataProducer\DataProducerPluginBase;
use Drupal\stanford_publication\Entity\CitationInterface;

/**
 * Renders a citation as a bibliography string in the given style.
 *
 * @codeCoverageIgnore
 */
#[DataProducer(
  id: "stanford_citation_bibliography",
  name: new TranslatableMarkup("Citation bibliography"),
  description: new TranslatableMarkup("Renders a citation in a bibliography style."),
  produces: new ContextDefinition(
    data_type: "string",
    label: new TranslatableMarkup("Bibliography"),
  ),
  consumes: [
    "citation" => new ContextDefinition(
      data_type: "entity",
      label: new TranslatableMarkup("Citation"),
    ),
    "style" => new ContextDefinition(
      data_type: "string",
      label: new TranslatableMarkup("Citation style"),
    ),
  ],
)]
class CitationBibliography extends DataProducerPluginBase {

  /**
   * Resolves the bibliography for the citation.
   *
   * @param \Drupal\stanford_publication\Entity\CitationInterface $citation
   *   The citation entity.
   * @param string $style
   *   The citation style language style ID.
   *
   * @return string
   *   The rendered bibliography.
   */
  public function resolve(CitationInterface $citation, string $style): string {
    return $citation->getBibliography($style);
  }

}
