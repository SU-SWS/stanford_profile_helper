<?php

namespace Drupal\stanford_profile_helper\Plugin\Validation\Constraint;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\path_alias\AliasManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the Menu link item constraint.
 */
class LinkFieldItemConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * Current request.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $request;

  /**
   * Path alias manager service.
   *
   * @var \Drupal\path_alias\AliasManagerInterface
   */
  protected $aliasManager;

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('path_alias.manager')
    );
  }

  /**
   * Validation constructor.
   *
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   Current request stack.
   * @param \Drupal\path_alias\AliasManagerInterface $alias_manager
   *   Path alias manager service.
   */
  public function __construct(RequestStack $request_stack, AliasManagerInterface $alias_manager) {
    $this->request = $request_stack->getCurrentRequest();
    $this->aliasManager = $alias_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $value */
    $link_uri = $value->get(0)?->get('uri')?->getString();
    if ($link_uri && str_contains($link_uri, $this->request->getSchemeAndHttpHost())) {
      $this->context->addViolation($constraint->absoluteLink);
    }
  }

}
