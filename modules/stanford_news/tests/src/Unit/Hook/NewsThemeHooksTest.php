<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_news\Unit\Hook;

use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_news\Hook\NewsThemeHooks;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Unit tests for NewsThemeHooks.
 */
#[Group('stanford_news')]
#[CoversClass(NewsThemeHooks::class)]
class NewsThemeHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_news\Hook\NewsThemeHooks
   */
  protected NewsThemeHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new NewsThemeHooks();
  }

  /**
   * Puts a route match returning the given "node" parameter in the container.
   */
  protected function setRouteNodeParameter($node): void {
    $routeMatch = $this->createMock(RouteMatchInterface::class);
    $routeMatch->method('getParameter')->with('node')->willReturn($node);

    $container = new ContainerBuilder();
    $container->set('current_route_match', $routeMatch);
    \Drupal::setContainer($container);
  }

  /**
   * The theme definition is returned exactly as expected.
   */
  public function testTheme(): void {
    $expected = [
      'signup_block' => [
        'variables' => [
          'form_action' => NULL,
        ],
        'template' => 'block/signup-block',
      ],
    ];
    $this->assertSame($expected, $this->hooks->theme());
  }

  /**
   * The flex class is appended to an empty attributes array.
   */
  public function testPreprocessFieldSuNewsDekEmptyVariables(): void {
    $variables = [];
    $this->hooks->preprocessFieldSuNewsDek($variables);
    $this->assertSame(['flex-10-of-12'], $variables['attributes']['class']);
  }

  /**
   * The flex class is appended alongside existing classes.
   */
  public function testPreprocessFieldSuNewsDekExistingClasses(): void {
    $variables = ['attributes' => ['class' => ['existing-class']]];
    $this->hooks->preprocessFieldSuNewsDek($variables);
    $this->assertSame(['existing-class', 'flex-10-of-12'], $variables['attributes']['class']);
  }

  /**
   * When there is no node on the route, nothing is attached.
   */
  public function testPageAttachmentsNoNode(): void {
    $this->setRouteNodeParameter(NULL);
    $attachments = [];
    $this->hooks->pageAttachments($attachments);
    $this->assertSame([], $attachments);
  }

  /**
   * A route parameter that is not a node is ignored.
   */
  public function testPageAttachmentsNonNodeEntity(): void {
    $term = $this->createMock(TermInterface::class);
    $this->setRouteNodeParameter($term);
    $attachments = [];
    $this->hooks->pageAttachments($attachments);
    $this->assertSame([], $attachments);
  }

  /**
   * A node of the wrong bundle is ignored.
   */
  public function testPageAttachmentsWrongBundle(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('page');
    $this->setRouteNodeParameter($node);
    $attachments = [];
    $this->hooks->pageAttachments($attachments);
    $this->assertSame([], $attachments);
  }

  /**
   * A stanford_news node without the hide-social field only attaches the
   * library.
   */
  public function testPageAttachmentsNoHideSocialField(): void {
    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('stanford_news');
    $node->method('hasField')->with('su_news_hide_social')->willReturn(FALSE);
    $node->expects($this->never())->method('get');
    $this->setRouteNodeParameter($node);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertArrayNotHasKey('drupalSettings', $attachments['#attached']);
    $this->assertSame(['stanford_news/news_node'], $attachments['#attached']['library']);
  }

  /**
   * A stanford_news node with the hide-social field attaches both the
   * drupalSettings and the library.
   */
  public function testPageAttachmentsWithHideSocialField(): void {
    $fieldList = $this->createMock(FieldItemListInterface::class);
    $fieldList->method('getString')->willReturn('1');

    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('stanford_news');
    $node->method('hasField')->with('su_news_hide_social')->willReturn(TRUE);
    $node->method('get')->with('su_news_hide_social')->willReturn($fieldList);
    $this->setRouteNodeParameter($node);

    $attachments = [];
    $this->hooks->pageAttachments($attachments);

    $this->assertTrue($attachments['#attached']['drupalSettings']['stanfordNews']['hideSocial']);
    $this->assertSame(['stanford_news/news_node'], $attachments['#attached']['library']);
  }

}
