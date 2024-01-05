<?php

namespace Drupal\stanford_decoupled\Plugin\Filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Clean Html' filter.
 *
 * @Filter(
 *   id = "su_clean_html",
 *   title = @Translation("Clean Html"),
 *   description = @Translation("Remove line breaks, html comments, and white space between tags. Requires <code>stanford_profile_helper.decoupled</code> state to be true."),
 *   type = Drupal\filter\Plugin\FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
 *   weight = 99
 * )
 */
class SuCleanHtml extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected EntityTypeManagerInterface $entityTypeManager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    if ($this->isDecoupled()) {
      // Remove line breaks.
      $text = preg_replace('/(\r\n)+|\r+|\n+|\t+/', ' ', $text);
      // Remove html comments.
      $text = preg_replace('/<!--.*?>/', '', $text);
      // Remove white space between tags.
      $text = preg_replace('/> +?</', '><', $text);
    }

    return new FilterProcessResult(trim($text));
  }

  /**
   * If any next site configs exist, the site can be considered decoupled.
   *
   * @return bool
   *   If the site is decoupled.
   */
  protected function isDecoupled(): bool {
    $next_site_count = $this->entityTypeManager->getStorage('next_site')
      ->getQuery()
      ->accessCheck(FALSE)
      ->count()
      ->execute();
    return $next_site_count > 0;
  }

}
