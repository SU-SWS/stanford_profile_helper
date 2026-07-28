<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_policy\Unit\Hook;

use Drupal\book\BookManagerInterface;
use Drupal\config_pages\ConfigPagesInterface;
use Drupal\config_pages\ConfigPagesLoaderServiceInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_fields\Event\BookOutlineUpdatedEvent;
use Drupal\stanford_policy\EventSubscriber\StanfordPolicySubscriber;
use Drupal\stanford_policy\Hook\StanfordPolicyHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Unit tests for StanfordPolicyHooks.
 */
#[Group('stanford_policy')]
#[CoversClass(StanfordPolicyHooks::class)]
class StanfordPolicyHooksTest extends UnitTestCase {

  /**
   * Mocked book manager service.
   *
   * @var \Drupal\book\BookManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected BookManagerInterface $bookManager;

  /**
   * Mocked config pages loader service.
   *
   * @var \Drupal\config_pages\ConfigPagesLoaderServiceInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected ConfigPagesLoaderServiceInterface $configPagesLoader;

  /**
   * Mocked entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface&\PHPUnit\Framework\MockObject\MockObject
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The hooks class under test.
   *
   * @var \Drupal\stanford_policy\Hook\StanfordPolicyHooks
   */
  protected StanfordPolicyHooks $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->bookManager = $this->createMock(BookManagerInterface::class);
    $this->configPagesLoader = $this->createMock(ConfigPagesLoaderServiceInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->hooks = new StanfordPolicyHooks($this->bookManager, $this->configPagesLoader, $this->entityTypeManager);
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    \Drupal::unsetContainer();
    parent::tearDown();
  }

  /**
   * The su_policy_related field widget gets the chosen flag set.
   */
  public function testOnWidgetFormAlterMatchingField(): void {
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getName')->willReturn('su_policy_related');

    $form_state = $this->createMock(FormStateInterface::class);
    $element = [];
    $context = ['items' => $items];

    $this->hooks->onWidgetFormAlter($element, $form_state, $context);

    $this->assertTrue($element['#chosen']);
  }

  /**
   * Other field widgets are left untouched.
   */
  public function testOnWidgetFormAlterNonMatchingField(): void {
    $items = $this->createMock(FieldItemListInterface::class);
    $items->method('getName')->willReturn('some_other_field');

    $form_state = $this->createMock(FormStateInterface::class);
    $element = [];
    $context = ['items' => $items];

    $this->hooks->onWidgetFormAlter($element, $form_state, $context);

    $this->assertArrayNotHasKey('#chosen', $element);
  }

  /**
   * Non policy_settings config pages are ignored on CRUD.
   */
  public function testOnEntityCrudNonPolicyBundle(): void {
    $entity = $this->createMock(ConfigPagesInterface::class);
    $entity->method('bundle')->willReturn('some_other_settings');
    $this->bookManager->expects($this->never())->method('getAllBooks');

    $this->hooks->onEntityCrud($entity);
  }

  /**
   * A policy_settings save/delete with no books does nothing further.
   */
  public function testOnEntityCrudPolicyBundleNoBooks(): void {
    $entity = $this->createMock(ConfigPagesInterface::class);
    $entity->method('bundle')->willReturn('policy_settings');
    $this->bookManager->method('getAllBooks')->willReturn([]);

    // No container is set up; if \Drupal::service() were invoked, this
    // would throw, proving the loop body never executes for an empty list.
    $this->hooks->onEntityCrud($entity);
    $this->addToAssertionCount(1);
  }

  /**
   * A policy_settings save/delete resaves every book node.
   */
  public function testOnEntityCrudPolicyBundleWithBooks(): void {
    $entity = $this->createMock(ConfigPagesInterface::class);
    $entity->method('bundle')->willReturn('policy_settings');
    $this->bookManager->method('getAllBooks')->willReturn([1 => [], 5 => []]);

    $subscriber = $this->createMock(StanfordPolicySubscriber::class);
    $resaved = [];
    $subscriber->expects($this->exactly(2))
      ->method('resaveBookNodes')
      ->willReturnCallback(function ($node_id) use (&$resaved) {
        $resaved[] = $node_id;
      });

    $container = new ContainerBuilder();
    $container->set('stanford_policy.event_subscriber', $subscriber);
    \Drupal::setContainer($container);

    $this->hooks->onEntityCrud($entity);

    $this->assertSame([1, 5], $resaved);
  }

  /**
   * A non stanford_policy node presave is untouched.
   */
  public function testOnEntityPreSaveWrongBundle(): void {
    $entity = $this->createMock(NodeInterface::class);
    $entity->method('bundle')->willReturn('some_other_type');
    $entity->expects($this->never())->method('set');
    $entity->expects($this->never())->method('setChangedTime');

    $this->hooks->onEntityPreSave($entity);
  }

  /**
   * A stanford_policy node not yet placed in the book outline gets its
   * title synced from the su_policy_title field.
   */
  public function testOnEntityPreSaveEmptyBookPid(): void {
    $entity = $this->createMock(NodeInterface::class);
    $entity->book = [];
    $entity->method('bundle')->willReturn('stanford_policy');

    $field_item = $this->createMock(FieldItemListInterface::class);
    $field_item->method('getString')->willReturn('New Title');
    $entity->method('get')->with('su_policy_title')->willReturn($field_item);

    $entity->expects($this->once())->method('set')->with('title', 'New Title');
    $entity->expects($this->once())->method('setChangedTime');

    $this->hooks->onEntityPreSave($entity);
  }

  /**
   * A stanford_policy node with book pid of -1 also gets its title synced.
   */
  public function testOnEntityPreSaveBookPidMinusOne(): void {
    $entity = $this->createMock(NodeInterface::class);
    $entity->book = ['pid' => -1];
    $entity->method('bundle')->willReturn('stanford_policy');

    $field_item = $this->createMock(FieldItemListInterface::class);
    $field_item->method('getString')->willReturn('Another Title');
    $entity->method('get')->with('su_policy_title')->willReturn($field_item);

    $entity->expects($this->once())->method('set')->with('title', 'Another Title');
    $entity->expects($this->once())->method('setChangedTime');

    $this->hooks->onEntityPreSave($entity);
  }

  /**
   * A stanford_policy node already placed in the book outline (positive
   * pid) is left untouched.
   */
  public function testOnEntityPreSaveBookPidSet(): void {
    $entity = $this->createMock(NodeInterface::class);
    $entity->book = ['pid' => 5];
    $entity->method('bundle')->willReturn('stanford_policy');
    $entity->expects($this->never())->method('set');
    $entity->expects($this->never())->method('setChangedTime');

    $this->hooks->onEntityPreSave($entity);
  }

  /**
   * The book admin edit form gets a submit handler when editing a
   * stanford_policy book node.
   */
  public function testOnFormAlterBookAdminEditMatchingBundle(): void {
    $book_node = $this->createMock(NodeInterface::class);
    $book_node->method('bundle')->willReturn('stanford_policy');

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getBuildInfo')->willReturn(['args' => [$book_node]]);

    $form = [];
    $this->hooks->onFormAlter($form, $form_state, 'book_admin_edit');

    $this->assertContains(
      [StanfordPolicyHooks::class, 'onBookAdminEditSubmit'],
      $form['#submit']
    );
  }

  /**
   * The book admin edit form is untouched for non stanford_policy books.
   */
  public function testOnFormAlterBookAdminEditNonMatchingBundle(): void {
    $book_node = $this->createMock(NodeInterface::class);
    $book_node->method('bundle')->willReturn('some_other_type');

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getBuildInfo')->willReturn(['args' => [$book_node]]);

    $form = [];
    $this->hooks->onFormAlter($form, $form_state, 'book_admin_edit');

    $this->assertArrayNotHasKey('#submit', $form);
  }

  /**
   * The stanford_policy node add form gets the title class attached and
   * never inspects the form build info.
   */
  public function testOnFormAlterPolicyNodeForm(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->never())->method('getBuildInfo');

    $form = ['su_policy_title' => []];
    $this->hooks->onFormAlter($form, $form_state, 'node_stanford_policy_form');

    $this->assertContains(
      'js-form-item-title-0-value',
      $form['su_policy_title']['#attributes']['class']
    );
  }

  /**
   * The stanford_policy node edit form gets the title class attached too.
   */
  public function testOnFormAlterPolicyNodeEditForm(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->never())->method('getBuildInfo');

    $form = ['su_policy_title' => []];
    $this->hooks->onFormAlter($form, $form_state, 'node_stanford_policy_edit_form');

    $this->assertContains(
      'js-form-item-title-0-value',
      $form['su_policy_title']['#attributes']['class']
    );
  }

  /**
   * Unrelated forms are left completely untouched.
   */
  public function testOnFormAlterOtherFormId(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->expects($this->never())->method('getBuildInfo');

    $form = [];
    $this->hooks->onFormAlter($form, $form_state, 'some_unrelated_form');

    $this->assertSame([], $form);
  }

  /**
   * The static submit handler dispatches the book outline updated event.
   */
  public function testOnBookAdminEditSubmit(): void {
    $book_node = $this->createMock(NodeInterface::class);

    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getBuildInfo')->willReturn(['args' => [$book_node]]);

    $dispatcher = $this->createMock(EventDispatcherInterface::class);
    $dispatcher->expects($this->once())
      ->method('dispatch')
      ->with(
        $this->isInstanceOf(BookOutlineUpdatedEvent::class),
        BookOutlineUpdatedEvent::OUTLINE_UPDATED
      );

    $container = new ContainerBuilder();
    $container->set('event_dispatcher', $dispatcher);
    \Drupal::setContainer($container);

    $form = [];
    StanfordPolicyHooks::onBookAdminEditSubmit($form, $form_state);
  }

}
