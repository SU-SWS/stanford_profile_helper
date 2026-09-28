<?php

namespace Drupal\Tests\stanford_decoupled\Unit\Plugin\Filter;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Field\EntityReferenceFieldItemListInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\GeneratedUrl;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\media\MediaInterface;
use Drupal\media\MediaSourceInterface;
use Drupal\node\NodeInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\stanford_decoupled\Plugin\Filter\SuCleanHtml;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the clean html filter plugin.
 */
#[Group('stanford_decoupled')]
class SuCleanHtmlTest extends UnitTestCase {

  public static function filterDataProvider() {
    return [
      'comments and block white space' => [
        "\r\n<!-- FOO > BAR\nBAZ-->\n\n<div>foo</div>\n\n\n<div>\r\nbar\r\n\r\nbaz</div>\r\n",
        "<div>foo</div><div>\nbar\n\nbaz</div>",
      ],
      'redundant titles' => [
        '<a href="foobar">foobar</a><article title="foobar">foobar</article><a href="foobar" title="foobar">foobar</a><a href="foobar" title="foobarbaz"><span>foobarbaz</span></a><a href="foobar" title="Other">foobar</a>',
        '<a href="foobar">foobar</a><article title="foobar">foobar</article><a href="foobar">foobar</a><a href="foobar"><span>foobarbaz</span></a><a href="foobar" title="Other">foobar</a>',
      ],
      'special characters in title' => [
        '<a href="#" title="Title / slash ^caret">Title / slash ^caret</a>',
        '<a href="#">Title / slash ^caret</a>',
      ],
      'inline white space is preserved' => [
        '<p><strong>foo</strong> <em>bar</em></p>',
        '<p><strong>foo</strong> <em>bar</em></p>',
      ],
      'code white space is preserved' => [
        "<pre><code><span>foo</span>\n  <span>bar</span></code></pre>",
        "<pre><code><span>foo</span>\n  <span>bar</span></code></pre>",
      ],
      'table white space' => [
        "<table>\n  <tbody>\n    <tr>\n      <td>foo</td>\n    </tr>\n  </tbody>\n</table>",
        '<table><tbody><tr><td>foo</td></tr></tbody></table>',
      ],
      'font awesome icons' => [
        '<i class="fa fa-star">&nbsp;</i>',
        '<i class="fa fa-star"></i>',
      ],
      'linkit attributes' => [
        '<a href="/foo" data-entity-type="node" data-entity-uuid="abc-123" data-entity-substitution="canonical">foo</a>',
        '<a href="/foo">foo</a>',
      ],
      'node links' => [
        '<a href="/node/1">foo</a><a href="/node/1#bar">bar</a><a href="/node/2?baz=1">baz</a><a href="/node/1/edit">edit</a>',
        '<a href="/foo-bar">foo</a><a href="/foo-bar#bar">bar</a><a href="/node/2?baz=1">baz</a><a href="/node/1/edit">edit</a>',
      ],
      'media links' => [
        '<a href="/media/10">file</a><a href="/media/10#page=2">page</a><a href="/media/11">video</a><a href="/media/12">missing</a><a href="/media/10/edit">edit</a>',
        '<a href="http://example.com/sites/default/files/foo.pdf">file</a><a href="http://example.com/sites/default/files/foo.pdf#page=2">page</a><a href="/media/11">video</a><a href="/media/12">missing</a><a href="/media/10/edit">edit</a>',
      ],
      'not decoupled' => [
        "<!-- foo -->\n<div>foo</div>\n<div><a href=\"/node/1\" data-entity-type=\"node\">foo</a></div>",
        "<!-- foo -->\n<div>foo</div>\n<div><a href=\"/foo-bar\" data-entity-type=\"node\">foo</a></div>",
        FALSE,
      ],
      'not decoupled entity links' => [
        '<a href="/media/10">file</a><a href="/media/11">video</a><a href="/node/1#bar">bar</a><a href="/node/2">missing</a>',
        '<a href="/sites/default/files/foo.pdf">file</a><a href="/media/11">video</a><a href="/foo-bar#bar">bar</a><a href="/node/2">missing</a>',
        FALSE,
      ],
    ];
  }

  /**
   * Test the clean html filter.
   */
  #[DataProvider('filterDataProvider')]
  public function testFilter($html, $expected, $decoupled = TRUE) {
    $config = [];
    $definition = ['provider' => 'stanford_profile_helper'];

    $entity_query = $this->createMock(QueryInterface::class);
    $entity_query->method('accessCheck')->willReturnSelf();
    $entity_query->method('count')->willReturnSelf();
    $entity_query->method('execute')->willReturn((int) $decoupled);

    $url = $this->createMock(Url::class);
    $url->method('toString')->willReturn((new GeneratedUrl())->setGeneratedUrl('/foo-bar'));

    $node = $this->createMock(NodeInterface::class);
    $node->method('toUrl')->willReturn($url);
    $node->method('getCacheTags')->willReturn(['node:1']);
    $node->method('getCacheContexts')->willReturn([]);
    $node->method('getCacheMaxAge')->willReturn(-1);

    $file = $this->createMock(FileInterface::class);
    $file->method('createFileUrl')
      ->willReturnCallback(fn($relative) => ($relative ? '' : 'http://example.com') . '/sites/default/files/foo.pdf');
    $file->method('getCacheTags')->willReturn(['file:1']);
    $file->method('getCacheContexts')->willReturn([]);
    $file->method('getCacheMaxAge')->willReturn(-1);

    $file_field = $this->createMock(EntityReferenceFieldItemListInterface::class);
    $file_field->method('referencedEntities')->willReturn([$file]);

    $file_media = $this->getMockMedia('field_media_file', $file_field);
    $oembed_media = $this->getMockMedia('field_media_oembed_video', $this->createMock(FieldItemListInterface::class));

    $storages = [
      'next_site' => $this->getMockStorage([], $entity_query),
      'node' => $this->getMockStorage([1 => $node]),
      'media' => $this->getMockStorage([10 => $file_media, 11 => $oembed_media]),
    ];

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('hasDefinition')->willReturn(TRUE);
    $entity_type_manager->method('getStorage')->willReturnCallback(fn($entity_type) => $storages[$entity_type]);

    $container = new ContainerBuilder();
    $container->set('entity_type.manager', $entity_type_manager);

    $filter = SuCleanHtml::create($container, $config, 'foo', $definition);
    $result = $filter->process($html, NULL);

    $this->assertEquals($expected, (string) $result);
  }

  /**
   * Get a mock entity storage.
   *
   * @param array $entities
   *   Keyed array of entities the storage can load.
   * @param \Drupal\Core\Entity\Query\QueryInterface|null $query
   *   Entity query.
   *
   * @return \Drupal\Core\Entity\EntityStorageInterface
   *   Mock storage.
   */
  protected function getMockStorage(array $entities, ?QueryInterface $query = NULL): EntityStorageInterface {
    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->method('loadMultiple')
      ->willReturnCallback(fn(array $ids) => array_intersect_key($entities, array_flip($ids)));
    if ($query) {
      $storage->method('getQuery')->willReturn($query);
    }
    return $storage;
  }

  /**
   * Get a mock media entity.
   *
   * @param string $source_field
   *   Media source field name.
   * @param \Drupal\Core\Field\FieldItemListInterface $field
   *   Source field item list.
   *
   * @return \Drupal\media\MediaInterface
   *   Mock media entity.
   */
  protected function getMockMedia(string $source_field, FieldItemListInterface $field): MediaInterface {
    $source = $this->createMock(MediaSourceInterface::class);
    $source->method('getConfiguration')->willReturn(['source_field' => $source_field]);

    $media = $this->createMock(MediaInterface::class);
    $media->method('getSource')->willReturn($source);
    $media->method('hasField')->willReturn(TRUE);
    $media->method('get')->with($source_field)->willReturn($field);
    $media->method('getCacheTags')->willReturn(['media:1']);
    $media->method('getCacheContexts')->willReturn([]);
    $media->method('getCacheMaxAge')->willReturn(-1);
    return $media;
  }

}
