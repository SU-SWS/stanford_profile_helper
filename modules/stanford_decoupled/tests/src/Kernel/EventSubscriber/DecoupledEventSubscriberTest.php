<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_decoupled\Kernel\EventSubscriber;

use Drupal\KernelTests\KernelTestBase;
use Drupal\next\Event\EntityActionEvent;
use Drupal\next\Event\EntityActionEventInterface;
use Drupal\stanford_decoupled\EventSubscriber\DecoupledEventSubscriber;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;

/**
 * Tests the state setting that pauses front end revalidations.
 */
#[Group('stanford_decoupled')]
#[RunTestsInSeparateProcesses]
class DecoupledEventSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'entity_usage',
    'next',
    'stanford_decoupled',
  ];

  /**
   * The subscriber under test.
   *
   * @var \Drupal\stanford_decoupled\EventSubscriber\DecoupledEventSubscriber
   */
  protected DecoupledEventSubscriber $subscriber;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->installSchema('stanford_decoupled', ['stanford_decoupled_revalidation']);
    $this->subscriber = $this->container->get('stanford_decoupled.event_subscriber');
    // Act like a hosted environment so the local CLI skip doesn't apply.
    putenv('AH_SITE_ENVIRONMENT=dev');
  }

  /**
   * {@inheritdoc}
   */
  protected function tearDown(): void {
    putenv('AH_SITE_ENVIRONMENT');
    parent::tearDown();
  }

  /**
   * Build a Next entity action event for testing.
   */
  protected function getEvent(): EntityActionEvent {
    $entity = User::create(['name' => 'foo']);
    return new EntityActionEvent($entity, EntityActionEventInterface::UPDATE_ACTION, [], '/foo');
  }

  /**
   * Revalidations propagate normally on a hosted environment.
   */
  public function testNotPaused(): void {
    $event = $this->getEvent();
    $this->subscriber->onNextEntityAction($event);
    $this->assertFalse($event->isPropagationStopped());
  }

  /**
   * The state setting stops revalidations, even on a hosted environment.
   */
  public function testPausedWithState(): void {
    \Drupal::state()->set(DecoupledEventSubscriber::STOP_PROPAGATION_STATE, TRUE);
    $event = $this->getEvent();
    $this->subscriber->onNextEntityAction($event);
    $this->assertTrue($event->isPropagationStopped());

    \Drupal::state()->delete(DecoupledEventSubscriber::STOP_PROPAGATION_STATE);
    $event = $this->getEvent();
    $this->subscriber->onNextEntityAction($event);
    $this->assertFalse($event->isPropagationStopped());
  }

  /**
   * Queued paths are held, not sent or deleted, while paused.
   */
  public function testQueueHeldWhilePaused(): void {
    $database = \Drupal::database();
    $database->insert('stanford_decoupled_revalidation')
      ->fields(['site' => 'foo', 'path' => '/bar'])
      ->execute();

    \Drupal::state()->set(DecoupledEventSubscriber::STOP_PROPAGATION_STATE, TRUE);
    $terminate = new TerminateEvent($this->container->get('http_kernel'), new Request(), new Response());
    $this->subscriber->onKernelTerminate($terminate);

    $count = (int) $database->select('stanford_decoupled_revalidation')
      ->countQuery()
      ->execute()
      ->fetchField();
    $this->assertEquals(1, $count);
  }

}
