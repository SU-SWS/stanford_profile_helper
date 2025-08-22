<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Plugin\Validation\Constraint;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\Validation\Attribute\Constraint;
use Symfony\Component\Validator\Constraint as SymfonyConstraint;

/**
 * Provides a Redirect Source Trash constraint.
 *
 * @see https://www.drupal.org/node/2015723
 */
#[Constraint(
  id: 'redirect_source_trash',
  label: new TranslatableMarkup('Redirect Source Trash', options: ['context' => 'Validation'])
)]
final class RedirectSourceTrashConstraint extends SymfonyConstraint {

  public string $message = 'The source path %path appears to be a valid path. The path may currently exist in the trash.';

}
