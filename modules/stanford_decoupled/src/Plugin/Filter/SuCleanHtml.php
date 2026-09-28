<?php

namespace Drupal\stanford_decoupled\Plugin\Filter;

use Drupal\Component\Utility\Html;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\filter\Attribute\Filter;
use Drupal\filter\FilterProcessResult;
use Drupal\filter\Plugin\FilterBase;
use Drupal\filter\Plugin\FilterInterface;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'Clean Html' filter.
 */
#[Filter(
  id: "su_clean_html",
  title: new TranslatableMarkup("Clean Html"),
  description: new TranslatableMarkup("Remove html comments, redundant attributes, and white space between block tags. Convert node and media links to their final urls."),
  type: FilterInterface::TYPE_TRANSFORM_IRREVERSIBLE,
  weight: 99
)]
class SuCleanHtml extends FilterBase implements ContainerFactoryPluginInterface {

  /**
   * Block level elements that can have white space between them removed.
   */
  const BLOCK_ELEMENTS = [
    'address', 'article', 'aside', 'blockquote', 'dd', 'details', 'dialog',
    'div', 'dl', 'dt', 'fieldset', 'figcaption', 'figure', 'footer', 'form',
    'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'header', 'hgroup', 'hr', 'li', 'main',
    'nav', 'ol', 'p', 'pre', 'section', 'summary', 'table', 'tbody', 'td',
    'tfoot', 'th', 'thead', 'tr', 'ul', 'caption', 'colgroup', 'col',
  ];

  /**
   * Linkit attributes that aren't necessary for decoupled sites.
   */
  const ENTITY_ATTRIBUTES = [
    'data-entity-type',
    'data-entity-uuid',
    'data-entity-substitution',
  ];

  /**
   * Static cache of the decoupled status.
   *
   * @var bool
   */
  protected bool $decoupled;

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
    $result = new FilterProcessResult();
    $dom = Html::load($text);
    $xpath = new \DOMXPath($dom);

    // Font awesome icons don't need the non-breaking space the editor adds.
    foreach ($xpath->query('//i[not(*)]') as $icon) {
      if (trim($icon->textContent, " \u{00A0}") === '') {
        $icon->textContent = '';
      }
    }

    $this->removeRedundantTitles($xpath);

    // A link around a media element doesn't work all the time. Convert links
    // to the entity paths.
    $link_entity_types = ['media', 'node'];
    $this->replaceEntityLinks($xpath, $result, $link_entity_types);

    if ($this->isDecoupled()) {
      foreach ($xpath->query('//comment()') as $comment) {
        $comment->parentNode->removeChild($comment);
      }
      $this->removeBlockWhiteSpace($xpath);

      foreach (self::ENTITY_ATTRIBUTES as $attribute) {
        foreach ($xpath->query("//*[@$attribute]") as $element) {
          $element->removeAttribute($attribute);
        }
      }
    }

    return $result->setProcessedText(trim(Html::serialize($dom)));
  }

  /**
   * Remove the title attribute from links if it matches the link text.
   *
   * @param \DOMXPath $xpath
   *   Document xpath.
   */
  protected function removeRedundantTitles(\DOMXPath $xpath): void {
    /** @var \DOMElement $link */
    foreach ($xpath->query('//a[@title]') as $link) {
      if (trim($link->textContent) === trim($link->getAttribute('title'))) {
        $link->removeAttribute('title');
      }
    }
  }

  /**
   * Remove white space only text nodes that sit between block elements.
   *
   * @param \DOMXPath $xpath
   *   Document xpath.
   */
  protected function removeBlockWhiteSpace(\DOMXPath $xpath): void {
    $text_nodes = $xpath->query('//text()[normalize-space() = ""][not(ancestor::pre or ancestor::code or ancestor::textarea)]');
    foreach ($text_nodes as $text_node) {
      if (
        $this->isBlockOrEmpty($text_node->previousSibling) &&
        $this->isBlockOrEmpty($text_node->nextSibling)
      ) {
        $text_node->parentNode->removeChild($text_node);
      }
    }
  }

  /**
   * Check if the sibling node is a block element or doesn't exist.
   *
   * @param \DOMNode|null $node
   *   Sibling DOM node.
   *
   * @return bool
   *   True if white space next to the node is insignificant.
   */
  protected function isBlockOrEmpty(?\DOMNode $node): bool {
    return !$node || ($node instanceof \DOMElement && in_array($node->tagName, self::BLOCK_ELEMENTS));
  }

  /**
   * Convert entity links, such as /node/###, to their final urls.
   *
   * @param \DOMXPath $xpath
   *   Document xpath.
   * @param \Drupal\filter\FilterProcessResult $result
   *   Filter result to add cacheable metadata.
   * @param string[] $entity_types
   *   Entity type ids whose links should be converted.
   */
  protected function replaceEntityLinks(\DOMXPath $xpath, FilterProcessResult $result, array $entity_types): void {
    $links = [];
    $pattern = '/^\/(' . implode('|', $entity_types) . ')\/(\d+)([?#].*)?$/';
    /** @var \DOMElement $link */
    foreach ($xpath->query('//a[@href]') as $link) {
      if (preg_match($pattern, $link->getAttribute('href'), $matches)) {
        $links[$matches[1]][$matches[2]][] = [$link, $matches[3] ?? ''];
      }
    }

    foreach ($links as $entity_type => $entity_links) {
      if (!$this->entityTypeManager->hasDefinition($entity_type)) {
        continue;
      }
      $entities = $this->entityTypeManager->getStorage($entity_type)
        ->loadMultiple(array_keys($entity_links));

      foreach ($entities as $id => $entity) {
        $url = $entity instanceof MediaInterface ? $this->getMediaFileUrl($entity, $result) : $this->getEntityUrl($entity, $result);
        if (!$url) {
          continue;
        }

        foreach ($entity_links[$id] as [$link, $suffix]) {
          $link->setAttribute('href', $url . $suffix);
        }
      }
    }
  }

  /**
   * Get the canonical url of the entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   Linked entity.
   * @param \Drupal\filter\FilterProcessResult $result
   *   Filter result to add cacheable metadata.
   *
   * @return string
   *   Entity url.
   */
  protected function getEntityUrl(EntityInterface $entity, FilterProcessResult $result): string {
    $url = $entity->toUrl()->toString(TRUE);
    $result->addCacheableDependency($entity)->addCacheableDependency($url);
    return $url->getGeneratedUrl();
  }

  /**
   * Get the url to the file on the media entity's source field.
   *
   * Decoupled sites need an absolute url since the files are served by Drupal.
   *
   * @param \Drupal\media\MediaInterface $media
   *   Linked media entity.
   * @param \Drupal\filter\FilterProcessResult $result
   *   Filter result to add cacheable metadata.
   *
   * @return string|null
   *   File url, or null if the media isn't file based, such as oembed media.
   */
  protected function getMediaFileUrl(MediaInterface $media, FilterProcessResult $result): ?string {
    $source_field = $media->getSource()->getConfiguration()['source_field'] ?? NULL;
    if (!$source_field || !$media->hasField($source_field)) {
      return NULL;
    }

    $field = $media->get($source_field);
    $file = $field instanceof EntityReferenceFieldItemListInterface ? ($field->referencedEntities()[0] ?? NULL) : NULL;
    if (!$file instanceof FileInterface) {
      return NULL;
    }

    $result->addCacheableDependency($media)->addCacheableDependency($file);
    return $file->createFileUrl(!$this->isDecoupled());
  }

  /**
   * If any next site configs exist, the site can be considered decoupled.
   *
   * @return bool
   *   If the site is decoupled.
   */
  protected function isDecoupled(): bool {
    if (!isset($this->decoupled)) {
      $this->decoupled = (bool) $this->entityTypeManager->getStorage('next_site')
        ->getQuery()
        ->accessCheck(FALSE)
        ->count()
        ->execute();
    }
    return $this->decoupled;
  }

}
