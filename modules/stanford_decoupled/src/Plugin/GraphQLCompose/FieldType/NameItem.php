<?php

declare(strict_types=1);

namespace Drupal\stanford_decoupled\Plugin\GraphQLCompose\FieldType;

use Drupal\Core\Field\FieldItemInterface;
use Drupal\graphql\GraphQL\Execution\FieldContext;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerItemInterface;
use Drupal\graphql_compose\Plugin\GraphQL\DataProducer\FieldProducerTrait;
use Drupal\graphql_compose\Plugin\GraphQLCompose\GraphQLComposeFieldTypeBase;

/**
 * {@inheritDoc}
 *
 * @codeCoverageIgnore Unclear how to test for this.
 *
 * @GraphQLComposeFieldType(
 *   id = "name",
 *   type_sdl = "NameType",
 * )
 */
class NameItem extends GraphQLComposeFieldTypeBase implements FieldProducerItemInterface {

  use FieldProducerTrait;

  /**
   * {@inheritdoc}
   */
  public function resolveFieldItem(FieldItemInterface $item, FieldContext $context) {
    return [
      'title' => $item->get('title')->getValue(),
      'given' => $item->get('given')->getValue(),
      'middle' => $item->get('middle')->getValue(),
      'family' => $item->get('family')->getValue(),
      'generational' => $item->get('generational')->getValue(),
      'credentials' => $item->get('credentials')->getValue(),
    ];
  }

}
