<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Hook;

use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Query\Delete;
use Drupal\Core\Database\Query\Select;
use Drupal\Core\Database\StatementInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\stanford_profile_helper\Hook\TaxonomyHooks;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for TaxonomyHooks.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(TaxonomyHooks::class)]
class TaxonomyHooksTest extends UnitTestCase {

  /**
   * The hook class under test.
   *
   * @var \Drupal\stanford_profile_helper\Hook\TaxonomyHooks
   */
  protected TaxonomyHooks $hooks;

  /**
   * Mocked database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected Connection $database;

  /**
   * Mocked route builder.
   *
   * @var \Drupal\Core\Routing\RouteBuilderInterface
   */
  protected RouteBuilderInterface $routeBuilder;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->database = $this->createMock(Connection::class);
    $this->routeBuilder = $this->createMock(RouteBuilderInterface::class);
    $this->hooks = new TaxonomyHooks($this->database, $this->routeBuilder);
  }

  /**
   * Builds a term mock whose 'parent' field returns the given values.
   */
  protected function mockTerm(string $originalParent, string $currentParent): TermInterface {
    $originalField = $this->createMock(FieldItemListInterface::class);
    $originalField->method('getString')->willReturn($originalParent);

    $original = $this->createMock(TermInterface::class);
    $original->method('get')->with('parent')->willReturn($originalField);

    $currentField = $this->createMock(FieldItemListInterface::class);
    $currentField->method('getString')->willReturn($currentParent);

    $term = $this->createMock(TermInterface::class);
    $term->method('getOriginal')->willReturn($original);
    $term->method('get')->with('parent')->willReturn($currentField);
    $term->method('id')->willReturn(42);

    return $term;
  }

  /**
   * When the parent term hasn't changed, nothing happens.
   */
  public function testTaxonomyTermUpdateWithUnchangedParentReturnsEarly(): void {
    $term = $this->mockTerm('5', '5');

    $this->database->expects($this->never())->method('select');
    $this->database->expects($this->never())->method('delete');
    $this->routeBuilder->expects($this->never())->method('rebuild');

    $this->hooks->taxonomyTermUpdate($term);
  }

  /**
   * When the parent changed but no matching menu link exists, nothing else
   * happens.
   */
  public function testTaxonomyTermUpdateWithChangedParentAndNoMenuLink(): void {
    $term = $this->mockTerm('5', '9');

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn(0);

    $countQuery = $this->createMock(Select::class);
    $countQuery->method('execute')->willReturn($statement);

    $select = $this->createMock(Select::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('countQuery')->willReturn($countQuery);

    $this->database->method('select')
      ->with('menu_tree', 'm')
      ->willReturn($select);

    $this->database->expects($this->never())->method('delete');
    $this->routeBuilder->expects($this->never())->method('rebuild');

    $this->hooks->taxonomyTermUpdate($term);
  }

  /**
   * When the parent changed and a matching menu link exists, it is deleted
   * and the router is rebuilt.
   */
  public function testTaxonomyTermUpdateWithChangedParentAndExistingMenuLink(): void {
    $term = $this->mockTerm('5', '9');

    $statement = $this->createMock(StatementInterface::class);
    $statement->method('fetchField')->willReturn(1);

    $countQuery = $this->createMock(Select::class);
    $countQuery->method('execute')->willReturn($statement);

    $select = $this->createMock(Select::class);
    $select->method('fields')->willReturnSelf();
    $select->method('condition')->willReturnSelf();
    $select->method('countQuery')->willReturn($countQuery);

    $delete = $this->createMock(Delete::class);
    $delete->method('condition')->willReturnSelf();
    $delete->expects($this->once())->method('execute');

    $this->database->method('select')
      ->with('menu_tree', 'm')
      ->willReturn($select);
    $this->database->expects($this->once())
      ->method('delete')
      ->with('menu_tree')
      ->willReturn($delete);

    $this->routeBuilder->expects($this->once())->method('rebuild');

    $this->hooks->taxonomyTermUpdate($term);
  }

}
