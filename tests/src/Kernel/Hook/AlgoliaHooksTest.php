<?php

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\search_api\IndexInterface;
use Drupal\search_api\Item\FieldInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api_algolia\SearchApiAlgoliaHelper;
use Drupal\stanford_profile_helper\Hook\AlgoliaHooks;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\HttpFoundation\Request;

/**
 * Test the Algolia hooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(AlgoliaHooks::class)]
class AlgoliaHooksTest extends SuProfileHelperKernelTestBase {

  /**
   * The Algolia hooks service.
   *
   * @var \Drupal\stanford_profile_helper\Hook\AlgoliaHooks
   */
  protected $algoliaHooks;

  /**
   * A test node.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritDoc}
   */
  public function register(\Drupal\Core\DependencyInjection\ContainerBuilder $container) {
    parent::register($container);
    // Add service alias to work around autowiring issue with Hook system.
    $container->setAlias(
      'Drupal\config_pages\ConfigPagesLoaderServiceInterface',
      'config_pages.loader'
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');

    // Create a test node type.
    NodeType::create(['type' => 'page', 'name' => 'Page'])->save();

    // Create test node.
    $this->node = Node::create([
      'type' => 'page',
      'title' => 'Test Node',
      'status' => 1,
    ]);
    $this->node->save();

    // Mock the config pages loader service to return empty string not null.
    $configPagesLoader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $configPagesLoader->method('getValue')->willReturn('');

    // Create a mock request.
    $request = Request::create('https://example.com/node/1');
    $requestStack = $this->container->get('request_stack');
    $requestStack->push($request);

    // Create the AlgoliaHooks service with mocked dependencies.
    $this->algoliaHooks = new AlgoliaHooks(
      $configPagesLoader,
      $requestStack,
      $this->container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritDoc}
   */
  protected function tearDown(): void {
    // Pop the request we pushed in setUp to avoid session errors.
    $requestStack = $this->container->get('request_stack');
    if ($requestStack->getCurrentRequest()) {
      $requestStack->pop();
    }
    parent::tearDown();
  }

  /**
   * Test node_update hook when node is unpublished.
   */
  public function testNodeUpdateUnpublish() {
    // Create a mock search_api_algolia.helper service.
    $helper = $this->createMock(SearchApiAlgoliaHelper::class);
    $helper->expects($this->once())
      ->method('entityDelete')
      ->with($this->node);

    $this->container->set('search_api_algolia.helper', $helper);

    // Unpublish the node.
    $this->node->setUnpublished();
    $this->node->save();
  }

  /**
   * Test node_update hook when node remains published.
   */
  public function testNodeUpdateRemainsPublished() {
    // Create a mock that should not be called.
    $helper = $this->createMock(SearchApiAlgoliaHelper::class);
    $helper->expects($this->never())
      ->method('entityDelete');

    $this->container->set('search_api_algolia.helper', $helper);

    // Update the node but keep it published.
    $this->node->setTitle('Updated Title');
    $this->node->save();
  }

  /**
   * Test alterObjects method with basic data.
   */
  public function testAlterObjectsBasic() {
    // Create a mock index.
    $index = $this->createMock(IndexInterface::class);
    $field = $this->createMock(FieldInterface::class);
    $field->method('getPropertyPath')->willReturn('entity:title');
    $index->method('getField')->willReturn($field);

    $objects = [
      [
        'title' => 'Test Title',
        'objectID' => 'test-uuid-123',
        'site_name' => 'Test Site',
        'search_api_datasource' => 'entity:node',
        'status' => 1,
      ],
    ];

    $items = [];

    $this->algoliaHooks->alterObjects($objects, $index, $items);

    // Verify title is moved to first position.
    $keys = array_keys($objects[0]);
    $this->assertEquals('title', $keys[0]);

    // Verify unwanted fields are removed.
    $this->assertArrayNotHasKey('search_api_datasource', $objects[0]);
    $this->assertArrayNotHasKey('status', $objects[0]);
  }

  /**
   * Test alterObjects with federated search enabled.
   */
  public function testAlterObjectsFederated() {
    // Mock config pages loader to return federated search enabled.
    $configPagesLoader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $configPagesLoader->method('getValue')
      ->willReturnMap([
        [
          'stanford_basic_site_settings',
          'su_site_algolia_fed',
          0,
          'value',
          TRUE,
        ],
        ['stanford_basic_site_settings', 'su_site_url', 0, 'uri', ''],
      ]);

    $request = Request::create('https://example.com/node/1');
    $requestStack = $this->container->get('request_stack');
    $requestStack->push($request);

    $algoliaHooks = new AlgoliaHooks(
      $configPagesLoader,
      $requestStack,
      $this->container->get('entity_type.manager')
    );

    // Create a mock index.
    $index = $this->createMock(IndexInterface::class);
    $field = $this->createMock(FieldInterface::class);
    $field->method('getPropertyPath')->willReturn('entity:title');
    $index->method('getField')->willReturn($field);

    $objects = [
      [
        'title' => 'Test Title',
        'objectID' => 'test-uuid-123',
        'site_name' => 'Test Site',
      ],
    ];

    $items = [];

    $algoliaHooks->alterObjects($objects, $index, $items);

    // Pop the request.
    $requestStack->pop();

    // Verify objectID is prefixed with hash.
    $this->assertStringContainsString(':', $objects[0]['objectID']);
    $parts = explode(':', $objects[0]['objectID']);
    $this->assertEquals(5, strlen($parts[0]));
    $this->assertEquals('test-uuid-123', $parts[1]);
  }

  /**
   * Test alterObjects with canonical URL replacement.
   */
  public function testAlterObjectsCanonicalUrl() {
    // Mock config pages loader to return canonical URL.
    $configPagesLoader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $configPagesLoader->method('getValue')
      ->willReturnMap([
        [
          'stanford_basic_site_settings',
          'su_site_url',
          0,
          'uri',
          'https://example.stanford.edu',
        ],
        [
          'stanford_basic_site_settings',
          'su_site_algolia_fed',
          0,
          'value',
          FALSE,
        ],
      ]);

    // Set up request with different host.
    $request = Request::create('https://test.example.com/node/1');
    $requestStack = $this->container->get('request_stack');
    $requestStack->push($request);

    $algoliaHooks = new AlgoliaHooks(
      $configPagesLoader,
      $requestStack,
      $this->container->get('entity_type.manager')
    );

    // Create a mock index.
    $index = $this->createMock(IndexInterface::class);
    $field = $this->createMock(FieldInterface::class);
    $field->method('getPropertyPath')->willReturn('entity:title');
    $index->method('getField')->willReturn($field);

    $objects = [
      [
        'title' => 'Test Title',
        'objectID' => 'test-uuid-123',
        'url' => 'https://test.example.com/node/1',
        'site_name' => 'Test Site',
      ],
    ];

    $items = [];

    $algoliaHooks->alterObjects($objects, $index, $items);

    // Pop the request.
    $requestStack->pop();

    // Verify URL is replaced with canonical URL.
    $this->assertEquals('https://example.stanford.edu/node/1', $objects[0]['url']);
  }

  /**
   * Test alterObjects with taxonomy term field handling.
   */
  public function testAlterObjectsTaxonomyTermField() {
    // Create a mock index.
    $index = $this->createMock(IndexInterface::class);
    $field = $this->createMock(FieldInterface::class);
    $field->method('getPropertyPath')
      ->willReturn('entity:field_tags:entity:name');
    $index->method('getField')->willReturn($field);

    $objects = [
      [
        'title' => 'Test Title',
        'objectID' => 'test-uuid-123',
        'site_name' => 'Test Site',
        'field_tags' => 'Single Tag',
      ],
    ];

    $items = [];

    $this->algoliaHooks->alterObjects($objects, $index, $items);

    // Verify single string is converted to array.
    $this->assertIsArray($objects[0]['field_tags']);
    $this->assertEquals(['Single Tag'], $objects[0]['field_tags']);
  }

  /**
   * Test adjustFiltersData method with taxonomy terms.
   */
  public function testAdjustFiltersData() {
    // Create a vocabulary.
    Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ])->save();

    // Create parent term.
    $parent_term = Term::create([
      'vid' => 'tags',
      'name' => 'Parent Term',
      'parent' => [0],
    ]);
    $parent_term->save();

    // Create child term.
    $child_term = Term::create([
      'vid' => 'tags',
      'name' => 'Child Term',
      'parent' => [$parent_term->id()],
    ]);
    $child_term->save();

    // Create a mock index with filters field.
    $index = $this->createMock(IndexInterface::class);
    $field = $this->createMock(FieldInterface::class);
    $field->method('getPropertyPath')
      ->willReturn('entity:field_tags:entity:tid');
    $index->method('getField')
      ->willReturnCallback(function($name) use ($field) {
        return str_starts_with($name, 'filters_') ? $field : NULL;
      });

    $objects = [
      [
        'title' => 'Test Title',
        'objectID' => 'test-uuid-123',
        'site_name' => 'Test Site',
        'filters_tags' => [$child_term->id()],
      ],
    ];

    $items = [];

    $this->algoliaHooks->alterObjects($objects, $index, $items);

    // Verify filters field is created and structured correctly.
    $this->assertArrayHasKey('filters', $objects[0]);
    $this->assertArrayNotHasKey('filters_tags', $objects[0]);
    $this->assertIsArray($objects[0]['filters']);
    $this->assertNotEmpty($objects[0]['filters']);
    $this->assertEquals('Child Term', $objects[0]['filters'][0]['name']);
    $this->assertEquals($child_term->uuid(), $objects[0]['filters'][0]['objectId']);
    $this->assertEquals($parent_term->label(), $objects[0]['filters'][0]['categories.lvl0']);
  }

}
