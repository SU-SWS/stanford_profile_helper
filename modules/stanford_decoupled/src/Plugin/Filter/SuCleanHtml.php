<?php

namespace Drupal\stanford_decoupled\Plugin\Filter;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Clean Html' filter.
 */
#[Filter(
  id: "su_clean_html",
  title: new TranslatableMarkup("Clean Html"),
  description: new TranslatableMarkup("Remove line breaks, html comments, and white space between tags."),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
  weight: 99
)]
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
    $text = $this->removeRedundantTitle($text);
    if (!$this->isDecoupled()) {
      return new FilterProcessResult($text);
    }

    // Remove line breaks.
    $text = preg_replace('/(\r\n)+|\r+|\n+|\t+/', ' ', $text);
    // Remove html comments.
    $text = preg_replace('/<!--.*?>/', '', $text);
    // Remove white space between tags.
    $text = preg_replace('/> +?</', '><', $text);

    // Remove link attributes that result from the linkit module. They aren't
    // necessary: data-entity-type data-entity-uuid data-entity-substitution.
    $text = preg_replace('/ data-entity-(type|uuid|substitution)="[^"]*"/', '', $text);

    $ns = $this->entityTypeManager->getStorage('node');

    // Convert /node/### links to the url of the node.
    preg_match_all('/href="\/node\/\d+"/', $text, $matches);
    foreach ($matches[0] as $match) {
      preg_match('/node\/(\d+)/', $match, $node_id);
      $node_id = $node_id[1];
      if ($url = $ns->load($node_id)?->toUrl()->toString()) {
        $text = str_replace($match, 'href="' . $url . '"', $text);
      }
    }

    return new FilterProcessResult(trim($text));
  }

  /**
   * Remove the title attribute if it matches the link text.
   *
   * @param string $text
   *   The text string to be filtered.
   *
   * @return string
   *   Modified text string.
   */
  protected function removeRedundantTitle($text): string {
    libxml_use_internal_errors(TRUE);
    $dom = new \DOMDocument();
    $dom->loadHTML('<body>' . $text, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    $xpath = new \DOMXPath($dom);
    /** @var \DOMElement $link */
    foreach ($xpath->query('//a[@title]') as $link) {
      $title = $link->getAttribute('title');
      if ($link->textContent == $title) {
        $regex_title = preg_quote($title, '/');
        $text = preg_replace('/<a([^>]*) title="' . $regex_title . '"([^>]*)>' . $regex_title . '<\/a>/', '<a$1$2>' . $title . '</a>', $text);
      }
    }
    return $text;
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
