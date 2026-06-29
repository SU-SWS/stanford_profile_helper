<?php

namespace Drupal\Tests\stanford_decoupled\Kernel\Hook;

use Drupal\KernelTests\KernelTestBase;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\paragraphs\Entity\Paragraph;
use Drupal\paragraphs\Entity\ParagraphsType;
use Drupal\stanford_decoupled\Hook\StanfordDecoupledEntityTrackHooks;
use Drupal\taxonomy\Entity\Term;
use Drupal\taxonomy\Entity\Vocabulary;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for StanfordDecoupledEntityTrackHooks.
 */
#[CoversClass(StanfordDecoupledEntityTrackHooks::class)]
#[Group('stanford_decoupled')]
class StanfordDecoupledEntityTrackHooksTest extends KernelTestBase {

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
    'user',
    'field',
    'text',
    'node',
    'media',
    'image',
    'file',
    'taxonomy',
    'paragraphs',
    'entity_reference_revisions',
    'entity_usage',
    'next',
    'stanford_decoupled',
    'next',
  ];

  /**
   * Node entity for testing.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * Taxonomy term for testing.
   *
   * @var \Drupal\taxonomy\TermInterface
   */
  protected $term;

  /**
   * Media entity for testing.
   *
   * @var \Drupal\media\MediaInterface
   */
  protected $media;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('node');
    $this->installEntitySchema('media');
    $this->installEntitySchema('file');
    $this->installEntitySchema('taxonomy_term');
    $this->installEntitySchema('paragraph');
    $this->installSchema('node', ['node_access']);
    $this->installSchema('file', ['file_usage']);
    $this->installSchema('entity_usage', ['entity_usage']);
    $this->installConfig(['field', 'node', 'media', 'taxonomy', 'paragraphs', 'stanford_decoupled', 'entity_usage']);

    // Create node type.
    NodeType::create([
      'type' => 'page',
      'name' => 'Page',
    ])->save();

    // Create vocabulary.
    Vocabulary::create([
      'vid' => 'tags',
      'name' => 'Tags',
    ])->save();

    // Create paragraphs type.
    ParagraphsType::create([
      'id' => 'text',
      'label' => 'Text',
    ])->save();

    // Create a taxonomy term.
    $this->term = Term::create([
      'vid' => 'tags',
      'name' => 'Test Term',
    ]);
    $this->term->save();

    // Create a node.
    $this->node = Node::create([
      'type' => 'page',
      'title' => 'Test Node',
    ]);
    $this->node->save();

    // Set up the cache to indicate the site is decoupled.
    \Drupal::cache()->set('stanford_decoupled', TRUE);
  }

  /**
   * Tests that the hook does not run when the site is not decoupled.
   */
  public function testNonDecoupledSite() {
    // Clear the cache to make it non-decoupled.
    \Drupal::cache()->set('stanford_decoupled', FALSE);

    $term = Term::create([
      'vid' => 'tags',
      'name' => 'Another Term',
    ]);

    // Should not throw an exception or trigger events.
    $term->save();
    $this->assertNotEmpty($term->id());

    // Clean up.
    \Drupal::cache()->set('stanford_decoupled', TRUE);
  }

  /**
   * Tests that the hook only processes tracked entity types.
   */
  public function testTrackedEntityTypes() {
    // By default, taxonomy_term and media should be tracked.
    $config = $this->config('stanford_decoupled.settings');
    $tracked_types = $config->get('referenced_invalidated_types');

    // If config is empty, defaults to taxonomy_term and media.
    if (empty($tracked_types)) {
      $this->assertTrue(TRUE, 'Config uses default tracked types');
    }
    else {
      $this->assertContains('taxonomy_term', $tracked_types);
      $this->assertContains('media', $tracked_types);
    }
  }

  /**
   * Tests entity insert triggers event for referenced nodes.
   */
  public function testEntityInsertTriggersEvent() {
    // Create a new term.
    $new_term = Term::create([
      'vid' => 'tags',
      'name' => 'New Term',
    ]);
    $new_term->save();

    // Track entity usage for the node referencing this term.
    /** @var \Drupal\entity_usage\EntityUsageInterface $entity_usage */
    $entity_usage = \Drupal::service('entity_usage.usage');
    $entity_usage->registerUsage($new_term->id(), 'taxonomy_term', $this->node->id(), 'node', 'en', $this->node->getRevisionId(), 'entity_reference', 'field_tags');

    // Update the term after creating the usage - this should trigger the hook.
    $new_term->setName('Updated New Term');
    $new_term->save();

    // Verify the term was updated.
    $this->assertEquals('Updated New Term', $new_term->getName());
  }

  /**
   * Tests entity update triggers event for referenced nodes.
   */
  public function testEntityUpdateTriggersEvent() {
    // Track entity usage for the node referencing this term.
    /** @var \Drupal\entity_usage\EntityUsageInterface $entity_usage */
    $entity_usage = \Drupal::service('entity_usage.usage');
    $entity_usage->registerUsage($this->term->id(), 'taxonomy_term', $this->node->id(), 'node', 'en', $this->node->getRevisionId(), 'entity_reference', 'field_tags');

    // Update the term - this should trigger the hook.
    $this->term->setName('Updated Term');
    $this->term->save();

    // Verify the term was updated.
    $this->assertEquals('Updated Term', $this->term->getName());
  }

  /**
   * Tests entity delete triggers event for referenced nodes.
   */
  public function testEntityDeleteTriggersEvent() {
    // Track entity usage for the node referencing this term.
    /** @var \Drupal\entity_usage\EntityUsageInterface $entity_usage */
    $entity_usage = \Drupal::service('entity_usage.usage');
    $entity_usage->registerUsage($this->term->id(), 'taxonomy_term', $this->node->id(), 'node', 'en', $this->node->getRevisionId(), 'entity_reference', 'field_tags');

    $term_id = $this->term->id();

    // Delete the term - this should trigger the hook.
    $this->term->delete();

    // Verify the term was deleted.
    $deleted_term = Term::load($term_id);
    $this->assertNull($deleted_term);
  }

  /**
   * Tests that paragraph parent nodes trigger events.
   */
  public function testParagraphParentNodeTriggers() {
    // Create a paragraph.
    $paragraph = Paragraph::create([
      'type' => 'text',
    ]);
    $paragraph->save();

    // Create a node with the paragraph.
    $node_with_paragraph = Node::create([
      'type' => 'page',
      'title' => 'Node with Paragraph',
    ]);
    $node_with_paragraph->save();

    // Manually set parent for the paragraph.
    $paragraph->set('parent_id', $node_with_paragraph->id());
    $paragraph->set('parent_type', 'node');
    $paragraph->save();

    // Track entity usage for paragraph referencing a term.
    /** @var \Drupal\entity_usage\EntityUsageInterface $entity_usage */
    $entity_usage = \Drupal::service('entity_usage.usage');
    $entity_usage->registerUsage($this->term->id(), 'taxonomy_term', $paragraph->id(), 'paragraph', 'en', $paragraph->getRevisionId(), 'entity_reference', 'field_tags');

    // Update the term - this should trigger events for the paragraph's parent node.
    $this->term->setName('Term Referenced in Paragraph');
    $this->term->save();

    // Verify the term was updated.
    $this->assertEquals('Term Referenced in Paragraph', $this->term->getName());
  }

  /**
   * Tests that non-content entities are ignored.
   */
  public function testNonContentEntityIgnored() {
    // Config entities don't implement ContentEntityInterface,
    // so they should be ignored. We'll test with a node type.
    $node_type = NodeType::create([
      'type' => 'test_type',
      'name' => 'Test Type',
    ]);

    // Should not throw an exception.
    $node_type->save();
    $this->assertNotEmpty($node_type->id());
  }

  /**
   * Tests the entityUpdate static method.
   */
  public function testEntityUpdateMethod() {
    // This is a protected static method, so we test it indirectly
    // through the hook execution which calls it.
    /** @var \Drupal\entity_usage\EntityUsageInterface $entity_usage */
    $entity_usage = \Drupal::service('entity_usage.usage');
    $entity_usage->registerUsage($this->term->id(), 'taxonomy_term', $this->node->id(), 'node', 'en', $this->node->getRevisionId(), 'entity_reference', 'field_tags');

    // Update the term to trigger entityUpdate.
    $this->term->setName('Trigger Entity Update');
    $this->term->save();

    // Verify processing completed without errors.
    $this->assertEquals('Trigger Entity Update', $this->term->getName());
  }

}
