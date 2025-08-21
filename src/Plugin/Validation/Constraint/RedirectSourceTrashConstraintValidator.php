<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Plugin\Validation\Constraint;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Redirect Source Trash constraint.
 */
class RedirectSourceTrashConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Constructs the object.
   */
  public function __construct(private readonly Connection $database) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): self {
    return new self($container->get('database'));
  }

  /**
   * {@inheritdoc}
   */
  public function validate(mixed $item, Constraint $constraint): void {
    if (!$item instanceof FieldItemListInterface) {
      throw new \InvalidArgumentException(
        sprintf('The validated value must be instance of \Drupal\Core\Field\FieldItemListInterface, %s was given.', get_debug_type($item))
      );
    }
    $alias = '/' . $item->getString();
    $query = $this->database->select('path_alias', 'p')->fields('p');
    $query->leftJoin('node_field_data', 'n', "n.nid = REPLACE(p.path, '/node/', '')");
    $result = $query->condition('p.alias', $alias)
      ->condition('deleted', '0', '>')
      ->countQuery()
      ->execute()
      ->fetchField();

    if ($result) {
      $this->context->addViolation($constraint->message, ['%path' => ltrim($alias, '/')]);
    }
  }

}
