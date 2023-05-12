<?php

namespace Drupal\stanford_profile_helper\Plugin\Filter;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\State\StateInterface;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Clean Html' filter.
 *
 * @Filter(
 *   id = "su_clean_html",
 *   title = @Translation("Clean Html"),
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
      $container->get('state')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, protected StateInterface $state) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public function process($text, $langcode) {
    $decoupled = (bool) \Drupal::state()
      ->get('stanford_profile_helper.decoupled', FALSE);

    if ($decoupled) {
      // Remove line breaks
      $text = preg_replace('/(\r\n)+|\r+|\n+|\t+/', ' ', $text);
      // Remove html comments.
      $text = preg_replace('/<!--.*?>/', '', $text);
      // Remove white space between tags.
      $text = preg_replace('/> +?</', '><', $text);
    }

    return new FilterProcessResult(trim($text));
  }

}
