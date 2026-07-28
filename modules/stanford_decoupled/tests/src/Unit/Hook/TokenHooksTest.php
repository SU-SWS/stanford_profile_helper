<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_decoupled\Unit\Hook;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\stanford_decoupled\Hook\TokenHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for TokenHooks.
 */
#[Group('stanford_decoupled')]
#[CoversClass(TokenHooks::class)]
class TokenHooksTest extends UnitTestCase {

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The hooks class under test.
   *
   * @var \Drupal\stanford_decoupled\Hook\TokenHooks
   */
  protected TokenHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->hooks = new TokenHooks($this->entityTypeManager);
    $this->hooks->setStringTranslation($this->getStringTranslationStub());
  }

  /**
   * Builds an entity type mock with the given group and label.
   */
  protected function mockEntityType(string $group, string $label): EntityTypeInterface {
    $entityType = $this->createMock(EntityTypeInterface::class);
    $entityType->method('getGroup')->willReturn($group);
    $entityType->method('getLabel')->willReturn($label);
    return $entityType;
  }

  /**
   * No entity type definitions at all: info stays empty.
   */
  public function testTokenInfoNoDefinitions(): void {
    $this->entityTypeManager->method('getDefinitions')->willReturn([]);
    $this->assertSame([], $this->hooks->tokenInfo());
  }

  /**
   * Only non-content entity types: info stays empty.
   */
  public function testTokenInfoOnlyNonContentTypes(): void {
    $this->entityTypeManager->method('getDefinitions')->willReturn([
      'config_type' => $this->mockEntityType('configuration', 'Config Type'),
    ]);
    $this->assertSame([], $this->hooks->tokenInfo());
  }

  /**
   * Content entity types get a 'uuid' token added; others don't.
   */
  public function testTokenInfoMixedTypes(): void {
    $this->entityTypeManager->method('getDefinitions')->willReturn([
      'node' => $this->mockEntityType('content', 'Content'),
      'config_type' => $this->mockEntityType('configuration', 'Config Type'),
      'media' => $this->mockEntityType('content', 'Media'),
    ]);

    $info = $this->hooks->tokenInfo();

    $this->assertArrayHasKey('node', $info['tokens']);
    $this->assertArrayHasKey('uuid', $info['tokens']['node']);
    $this->assertArrayHasKey('media', $info['tokens']);
    $this->assertArrayNotHasKey('config_type', $info['tokens']);

    $this->assertSame('Content UUID', (string) $info['tokens']['node']['uuid']['name']);
    $this->assertSame(
      'The Universal Unique Identifier of Content',
      (string) $info['tokens']['node']['uuid']['description']
    );
  }

  /**
   * Empty $data[$type]: no replacements produced.
   */
  public function testTokensEmptyData(): void {
    $replacements = $this->hooks->tokens('node', ['uuid' => '[node:uuid]'], []);
    $this->assertSame([], $replacements);
  }

  /**
   * $data[$type] is not a ContentEntityInterface: no replacements produced.
   */
  public function testTokensNonContentEntity(): void {
    $data = ['node' => new \stdClass()];
    $replacements = $this->hooks->tokens('node', ['uuid' => '[node:uuid]'], $data);
    $this->assertSame([], $replacements);
  }

  /**
   * $data[$type] entirely missing: no replacements produced.
   */
  public function testTokensMissingDataKey(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $data = ['other_type' => $entity];
    $replacements = $this->hooks->tokens('node', ['uuid' => '[node:uuid]'], $data);
    $this->assertSame([], $replacements);
  }

  /**
   * Content entity with a 'uuid' token: replacement is the entity's uuid.
   */
  public function testTokensUuid(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $entity->method('uuid')->willReturn('abc-123');

    $data = ['node' => $entity];
    $tokens = ['uuid' => '[node:uuid]'];
    $replacements = $this->hooks->tokens('node', $tokens, $data);

    $this->assertSame(['[node:uuid]' => 'abc-123'], $replacements);
  }

  /**
   * Content entity with an unrecognized token name: no replacement added.
   */
  public function testTokensUnknownTokenName(): void {
    $entity = $this->createMock(ContentEntityInterface::class);
    $data = ['node' => $entity];
    $tokens = ['title' => '[node:title]'];
    $replacements = $this->hooks->tokens('node', $tokens, $data);

    $this->assertSame([], $replacements);
  }

}
