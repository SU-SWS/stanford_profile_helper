<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_decoupled\Kernel\Hook;

use Drupal\config_pages\Entity\ConfigPagesType;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\KernelTests\KernelTestBase;
use Drupal\media\Entity\MediaType;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\stanford_decoupled\Hook\StanfordDecoupledHooks;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Kernel tests for StanfordDecoupledHooks.
 */
class StanfordDecoupledHooksTest extends KernelTestBase {

  /**
   * Disable strict config schema checking for this test.
   *
   * @var bool
   */
  protected $strictConfigSchema = FALSE;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'text',
    'user',
    'node',
    'taxonomy',
    'media',
    'file',
    'image',
    'config_pages',
    'stanford_decoupled',
    'entity_usage',
    'next',
  ];

  /**
   * The hooks service.
   *
   * @var \Drupal\stanford_decoupled\Hook\StanfordDecoupledHooks
   */
  protected $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installConfig(['entity_usage']);
    $this->container->get('module_installer')->install(['paragraphs']);

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installEntitySchema('paragraph');
    $this->installEntitySchema('config_pages');
    $this->installConfig([
      'field',
      'node',
      'taxonomy',
      'media',
      'system',
      'file',
      'image',
    ]);

    // Initialize the GraphQL config.
    $this->config('graphql_compose.settings')->save();

    $this->hooks = new StanfordDecoupledHooks($this->container->get('config.factory'), $this->container->get('current_route_match'));
  }

  /**
   * Test onNodeTypeCreate sets GraphQL config correctly.
   */
  public function testOnNodeTypeCreate(): void {
    $nodeType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);

    $this->hooks->onNodeTypeCreate($nodeType);

    $config = $this->config('graphql_compose.settings');
    $this->assertTrue($config->get('entity_config.node.article.enabled'));
    $this->assertTrue($config->get('entity_config.node.article.query_load_enabled'));
    $this->assertTrue($config->get('entity_config.node.article.edges_enabled'));
    $this->assertTrue($config->get('entity_config.node.article.routes_enabled'));
  }

  /**
   * Test onNodeTypeCreate with different node type IDs.
   */
  public function testOnNodeTypeCreateMultipleTypes(): void {
    $pageType = NodeType::create([
      'type' => 'page',
      'name' => 'Basic Page',
    ]);

    $this->hooks->onNodeTypeCreate($pageType);

    $config = $this->config('graphql_compose.settings');
    $this->assertTrue($config->get('entity_config.node.page.enabled'));
    $this->assertTrue($config->get('entity_config.node.page.query_load_enabled'));
    $this->assertTrue($config->get('entity_config.node.page.edges_enabled'));
    $this->assertTrue($config->get('entity_config.node.page.routes_enabled'));

    $newsType = NodeType::create([
      'type' => 'news',
      'name' => 'News',
    ]);

    $this->hooks->onNodeTypeCreate($newsType);

    $config = $this->config('graphql_compose.settings');
    $this->assertTrue($config->get('entity_config.node.news.enabled'));
    $this->assertTrue($config->get('entity_config.node.news.query_load_enabled'));
  }

  /**
   * Test onEntityBundleCreate with taxonomy vocabulary.
   */
  public function testOnEntityBundleCreateTaxonomy(): void {
    $vocabulary = Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ]);

    $this->hooks->onEntityBundleCreate($vocabulary);

    $config = $this->config('graphql_compose.settings');
    $this->assertTrue($config->get('entity_config.taxonomy_term.tags.enabled'));
    $this->assertTrue($config->get('entity_config.taxonomy_term.tags.query_load_enabled'));
  }

  /**
   * Test onEntityBundleCreate with media type.
   */
  public function testOnEntityBundleCreateMedia(): void {
    $mediaType = MediaType::create([
      'id' => 'image',
      'label' => 'Image',
      'source' => 'image',
    ]);

    $this->hooks->onEntityBundleCreate($mediaType);

    $config = $this->config('graphql_compose.settings');
    $this->assertTrue($config->get('entity_config.media.image.enabled'));
    $this->assertTrue($config->get('entity_config.media.image.query_load_enabled'));
  }

  /**
   * Test onEntityBundleCreate with paragraph type.
   */
  public function testOnEntityBundleCreateParagraph(): void {
    $paragraphType = ParagraphsType::create([
      'id' => 'text_block',
      'label' => 'Text Block',
    ]);

    $this->hooks->onEntityBundleCreate($paragraphType);

    $config = $this->config('graphql_compose.settings');
    $this->assertTrue($config->get('entity_config.paragraph.text_block.enabled'));
    $this->assertTrue($config->get('entity_config.paragraph.text_block.query_load_enabled'));
  }

  /**
   * Test onEntityBundleCreate with config pages type.
   */
  public function testOnEntityBundleCreateConfigPages(): void {
    $configPagesType = ConfigPagesType::create([
      'id' => 'site_settings',
      'label' => 'Site Settings',
    ]);

    $this->hooks->onEntityBundleCreate($configPagesType);

    $config = $this->config('graphql_compose.settings');
    $this->assertTrue($config->get('entity_config.config_pages.site_settings.enabled'));
    $this->assertTrue($config->get('entity_config.config_pages.site_settings.query_load_enabled'));
  }

  /**
   * Test onFieldConfigCreate enables field in GraphQL.
   */
  public function testOnFieldConfigCreate(): void {
    // Create a node type first.
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    // Create field storage.
    FieldStorageConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();

    // Create field instance.
    $field = FieldConfig::create([
      'field_name' => 'field_test',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Test Field',
    ]);

    $this->hooks->onFieldConfigCreate($field);

    $config = $this->config('graphql_compose.settings');
    $this->assertTrue($config->get('field_config.node.article.field_test.enabled'));
  }

  /**
   * Test onFieldConfigCreate with different entity types.
   */
  public function testOnFieldConfigCreateMediaField(): void {
    // Create a media type.
    MediaType::create([
      'id' => 'image',
      'label' => 'Image',
      'source' => 'image',
    ])->save();

    // Create field storage.
    FieldStorageConfig::create([
      'field_name' => 'field_caption',
      'entity_type' => 'media',
      'type' => 'string',
    ])->save();

    // Create field instance.
    $field = FieldConfig::create([
      'field_name' => 'field_caption',
      'entity_type' => 'media',
      'bundle' => 'image',
      'label' => 'Caption',
    ]);

    $this->hooks->onFieldConfigCreate($field);

    $config = $this->config('graphql_compose.settings');
    $this->assertTrue($config->get('field_config.media.image.field_caption.enabled'));
  }

  /**
   * Test onFieldConfigCreate with taxonomy term.
   */
  public function testOnFieldConfigCreateTaxonomyField(): void {
    // Create a vocabulary.
    Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ])->save();

    // Create field storage.
    FieldStorageConfig::create([
      'field_name' => 'field_color',
      'entity_type' => 'taxonomy_term',
      'type' => 'string',
    ])->save();

    // Create field instance.
    $field = FieldConfig::create([
      'field_name' => 'field_color',
      'entity_type' => 'taxonomy_term',
      'bundle' => 'tags',
      'label' => 'Color',
    ]);

    $this->hooks->onFieldConfigCreate($field);

    $config = $this->config('graphql_compose.settings');
    $this->assertTrue($config->get('field_config.taxonomy_term.tags.field_color.enabled'));
  }

  /**
   * Test setGraphqlConfig method via reflection.
   */
  public function testSetGraphqlConfig(): void {
    $reflection = new \ReflectionClass($this->hooks);
    $method = $reflection->getMethod('setGraphqlConfig');
    $method->setAccessible(TRUE);

    $method->invoke($this->hooks, 'test.config.key', 'test_value');

    $config = $this->config('graphql_compose.settings');
    $this->assertEquals('test_value', $config->get('test.config.key'));
  }

  /**
   * Test setGraphqlConfig with array values.
   */
  public function testSetGraphqlConfigArrayValue(): void {
    $reflection = new \ReflectionClass($this->hooks);
    $method = $reflection->getMethod('setGraphqlConfig');
    $method->setAccessible(TRUE);

    $testArray = [
      'enabled' => TRUE,
      'setting1' => 'value1',
      'setting2' => FALSE,
    ];

    $method->invoke($this->hooks, 'test.array.key', $testArray);

    $config = $this->config('graphql_compose.settings');
    $this->assertEquals($testArray, $config->get('test.array.key'));
    $this->assertTrue($config->get('test.array.key.enabled'));
    $this->assertEquals('value1', $config->get('test.array.key.setting1'));
    $this->assertFalse($config->get('test.array.key.setting2'));
  }

  /**
   * Test multiple hook invocations don't overwrite each other.
   */
  public function testMultipleHookInvocations(): void {
    // Create first node type.
    $articleType = NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ]);
    $this->hooks->onNodeTypeCreate($articleType);

    // Create second node type.
    $pageType = NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ]);
    $this->hooks->onNodeTypeCreate($pageType);

    // Both should be enabled.
    $config = $this->config('graphql_compose.settings');
    $this->assertTrue($config->get('entity_config.node.article.enabled'));
    $this->assertTrue($config->get('entity_config.node.page.enabled'));
  }

  /**
   * Test config key generation for node types.
   */
  public function testNodeTypeConfigKeyGeneration(): void {
    $nodeType = NodeType::create([
      'type' => 'test_content_type',
      'name' => 'Test Content Type',
    ]);

    $this->hooks->onNodeTypeCreate($nodeType);

    $config = $this->config('graphql_compose.settings');
    // Verify the config key format is correct.
    $this->assertTrue($config->get('entity_config.node.test_content_type.enabled'));
  }

  /**
   * Test config key generation for fields.
   */
  public function testFieldConfigKeyGeneration(): void {
    NodeType::create([
      'type' => 'article',
      'name' => 'Article',
    ])->save();

    FieldStorageConfig::create([
      'field_name' => 'field_test_field',
      'entity_type' => 'node',
      'type' => 'string',
    ])->save();

    $field = FieldConfig::create([
      'field_name' => 'field_test_field',
      'entity_type' => 'node',
      'bundle' => 'article',
      'label' => 'Test Field',
    ]);

    $this->hooks->onFieldConfigCreate($field);

    $config = $this->config('graphql_compose.settings');
    // Verify the config key format is correct.
    $this->assertTrue($config->get('field_config.node.article.field_test_field.enabled'));
  }

}
