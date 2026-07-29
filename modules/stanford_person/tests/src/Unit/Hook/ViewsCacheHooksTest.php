<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_person\Unit\Hook;

use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\node\NodeInterface;
use Drupal\stanford_person\Hook\ViewsCacheHooks;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\cache\CachePluginBase;
use Drupal\views\ViewExecutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for ViewsCacheHooks.
 */
#[Group('stanford_person')]
#[CoversClass(ViewsCacheHooks::class)]
class ViewsCacheHooksTest extends UnitTestCase {

  /**
   * The hooks class under test.
   *
   * @var \Drupal\stanford_person\Hook\ViewsCacheHooks
   */
  protected ViewsCacheHooks $hooks;

  /**
   * The cache plugin mock, unused by the hook implementation.
   *
   * @var \PHPUnit\Framework\MockObject\MockObject&\Drupal\views\Plugin\views\cache\CachePluginBase
   */
  protected $cachePlugin;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new ViewsCacheHooks();
    $this->cachePlugin = $this->createMock(CachePluginBase::class);
  }

  /**
   * Sets a mocked cache_tags.invalidator service in the container.
   *
   * @return \PHPUnit\Framework\MockObject\MockObject&\Drupal\Core\Cache\CacheTagsInvalidatorInterface
   *   The invalidator mock so callers can set expectations.
   */
  protected function setUpInvalidator() {
    $invalidator = $this->createMock(CacheTagsInvalidatorInterface::class);
    $container = new ContainerBuilder();
    $container->set('cache_tags.invalidator', $invalidator);
    \Drupal::setContainer($container);
    return $invalidator;
  }

  /**
   * A mocked view with an id() and dynamic filter/current_display state.
   */
  protected function getViewMock(string $id, ?string $current_display = NULL) {
    $view = $this->createMock(ViewExecutable::class);
    $view->method('id')->willReturn($id);
    if ($current_display !== NULL) {
      $view->current_display = $current_display;
    }
    return $view;
  }

  /**
   * The stanford_person view swaps the node_list tag for per-type tags.
   */
  public function testViewsPostRenderStanfordPerson() {
    $view = $this->getViewMock('stanford_person');
    $view->filter = [
      'type' => (object) ['value' => ['stanford_person']],
    ];
    $output = [
      '#cache' => ['tags' => ['node_list', 'other_tag']],
    ];

    $this->hooks->viewsPostRender($view, $output, $this->cachePlugin);

    $this->assertNotContains('node_list', $output['#cache']['tags']);
    $this->assertContains('node_list:stanford_person', $output['#cache']['tags']);
    $this->assertContains('stanford_person/views', $output['#attached']['library']);
  }

  /**
   * The taxonomy_term_pages view only attaches the library on people_terms.
   */
  public function testViewsPostRenderTaxonomyTermPagesPeopleTerms() {
    $view = $this->getViewMock('taxonomy_term_pages', 'people_terms');
    $output = [];

    $this->hooks->viewsPostRender($view, $output, $this->cachePlugin);

    $this->assertContains('stanford_person/views', $output['#attached']['library']);
  }

  /**
   * The taxonomy_term_pages view does nothing on other displays.
   */
  public function testViewsPostRenderTaxonomyTermPagesOtherDisplay() {
    $view = $this->getViewMock('taxonomy_term_pages', 'some_other_display');
    $output = [];

    $this->hooks->viewsPostRender($view, $output, $this->cachePlugin);

    $this->assertArrayNotHasKey('#attached', $output);
  }

  /**
   * The stanford_person_list_terms_first view attaches the library and
   * falls through into the stanford_person_terms cache tag logic.
   */
  public function testViewsPostRenderListTermsFirstFallsThrough() {
    $view = $this->getViewMock('stanford_person_list_terms_first');
    $view->filter = [
      'vid' => (object) ['value' => ['stanford_person_types']],
    ];
    $output = [
      '#cache' => ['tags' => ['term_list', 'other_tag']],
    ];

    $this->hooks->viewsPostRender($view, $output, $this->cachePlugin);

    $this->assertContains('stanford_person/views', $output['#attached']['library']);
    $this->assertNotContains('term_list', $output['#cache']['tags']);
    $this->assertContains('term_list:stanford_person_types', $output['#cache']['tags']);
  }

  /**
   * The stanford_person_terms view swaps the term_list tag for per-vid tags.
   */
  public function testViewsPostRenderStanfordPersonTerms() {
    $view = $this->getViewMock('stanford_person_terms');
    $view->filter = [
      'vid' => (object) ['value' => ['stanford_person_types']],
    ];
    $output = [
      '#cache' => ['tags' => ['term_list', 'other_tag']],
    ];

    $this->hooks->viewsPostRender($view, $output, $this->cachePlugin);

    $this->assertArrayNotHasKey('#attached', $output);
    $this->assertNotContains('term_list', $output['#cache']['tags']);
    $this->assertContains('term_list:stanford_person_types', $output['#cache']['tags']);
  }

  /**
   * A view id with no matching case leaves output untouched.
   */
  public function testViewsPostRenderNoMatch() {
    $view = $this->getViewMock('some_unrelated_view');
    $output = ['foo' => 'bar'];

    $this->hooks->viewsPostRender($view, $output, $this->cachePlugin);

    $this->assertSame(['foo' => 'bar'], $output);
  }

  /**
   * Saving a stanford_person node invalidates its node_list cache tag.
   */
  public function testNodePresaveStanfordPerson() {
    $invalidator = $this->setUpInvalidator();
    $invalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['node_list:stanford_person']);

    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('stanford_person');

    $this->hooks->nodePresave($node);
  }

  /**
   * Saving a non-person node does not invalidate any cache tags.
   */
  public function testNodePresaveOtherBundle() {
    $invalidator = $this->setUpInvalidator();
    $invalidator->expects($this->never())->method('invalidateTags');

    $node = $this->createMock(NodeInterface::class);
    $node->method('bundle')->willReturn('article');

    $this->hooks->nodePresave($node);
  }

  /**
   * Saving a stanford_person_types term invalidates its term_list cache tag.
   */
  public function testTaxonomyTermPresaveStanfordPersonTypes() {
    $invalidator = $this->setUpInvalidator();
    $invalidator->expects($this->once())
      ->method('invalidateTags')
      ->with(['term_list:stanford_person_types']);

    $term = $this->createMock(TermInterface::class);
    $term->method('bundle')->willReturn('stanford_person_types');

    $this->hooks->taxonomyTermPresave($term);
  }

  /**
   * Saving a term from another vocabulary does not invalidate cache tags.
   */
  public function testTaxonomyTermPresaveOtherBundle() {
    $invalidator = $this->setUpInvalidator();
    $invalidator->expects($this->never())->method('invalidateTags');

    $term = $this->createMock(TermInterface::class);
    $term->method('bundle')->willReturn('tags');

    $this->hooks->taxonomyTermPresave($term);
  }

}
