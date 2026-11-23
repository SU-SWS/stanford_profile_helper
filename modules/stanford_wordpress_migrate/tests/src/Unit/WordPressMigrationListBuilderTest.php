<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\stanford_wordpress_migrate\WordPressMigrationListBuilder;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for WordPressMigrationListBuilder.
 */
#[Group('stanford_wordpress_migrate')]
class WordPressMigrationListBuilderTest extends UnitTestCase {

  /**
   * The list builder under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\WordPressMigrationListBuilder
   */
  protected $listBuilder;

  /**
   * Mock entity type.
   *
   * @var \Drupal\Core\Entity\EntityTypeInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityType;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityType = $this->createMock(EntityTypeInterface::class);
    $storage = $this->createMock(EntityStorageInterface::class);

    $this->listBuilder = new WordPressMigrationListBuilder($this->entityType, $storage);
  }

  /**
   * Test buildHeader method.
   */
  public function testBuildHeader(): void {
    // Mock the t() method by using a simple callback.
    $stringTranslation = $this->getStringTranslationStub();
    $this->listBuilder->setStringTranslation($stringTranslation);

    $header = $this->listBuilder->buildHeader();

    $this->assertArrayHasKey('label', $header);
    $this->assertArrayHasKey('base_url', $header);
    $this->assertArrayHasKey('status', $header);
  }

}
