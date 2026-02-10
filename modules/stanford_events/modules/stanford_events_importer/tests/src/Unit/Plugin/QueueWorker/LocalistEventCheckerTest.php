<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_events_importer\Unit\Plugin\QueueWorker;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\NodeInterface;
use Drupal\stanford_events_importer\Plugin\QueueWorker\LocalistEventChecker;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Utils;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests for LocalistEventChecker queue worker.
 */
#[Group('stanford_events_importer')]
#[CoversClass(LocalistEventChecker::class)]
class LocalistEventCheckerTest extends UnitTestCase {

  /**
   * The HTTP client mock.
   *
   * @var \GuzzleHttp\Client|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * The entity type manager mock.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The queue worker plugin.
   *
   * @var \Drupal\stanford_events_importer\Plugin\QueueWorker\LocalistEventChecker
   */
  protected $queueWorker;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->getMockBuilder(Client::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);

    $container = new ContainerBuilder();
    $container->set('http_client', $this->httpClient);
    $container->set('entity_type.manager', $this->entityTypeManager);
    $this->queueWorker = LocalistEventChecker::create($container, [], 'localist_event_checker', ['id' => 'localist_event_checker'] );
  }

  /**
   * Tests processItem when the event instance exists on Localist.
   */
  public function testProcessItemEventInstanceExists(): void {
    $sourceId = 12345;
    $destId = 67890;
    $instanceId = 54321;

    $responseData = json_encode([
      'event' => [
        'event_instances' => [
          ['event_instance' => ['id' => $instanceId]],
        ],
      ],
    ]);
    $response = new Response(200, [], Utils::streamFor($responseData));
    $this->httpClient->expects($this->once())
      ->method('get')
      ->with("https://events.stanford.edu/api/2/events/$sourceId", ['timeout' => 5])
      ->willReturn($response);

    $this->entityTypeManager->expects($this->never())
      ->method('getStorage');

    $this->queueWorker->processItem([$sourceId, $destId, $instanceId]);
  }

  /**
   * Tests processItem when the event exists but instance does not.
   */
  public function testProcessItemEventExistsInstanceDoesNot(): void {
    $sourceId = 12345;
    $destId = 67890;
    $instanceId = 54321;

    $responseData = json_encode([
      'event' => [
        'event_instances' => [
          ['event_instance' => ['id' => 99999]],
        ],
      ],
    ]);
    $response = new Response(200, [], Utils::streamFor($responseData));
    $this->httpClient->expects($this->once())
      ->method('get')
      ->with("https://events.stanford.edu/api/2/events/$sourceId", ['timeout' => 5])
      ->willReturn($response);

    $node = $this->createMock(NodeInterface::class);
    $node->expects($this->once())
      ->method('delete');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('load')
      ->with($destId)
      ->willReturn($node);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $this->queueWorker->processItem([$sourceId, $destId, $instanceId]);
  }

  /**
   * Tests processItem when the event is not found on Localist.
   */
  public function testProcessItemEventNotFound(): void {
    $sourceId = 12345;
    $destId = 67890;
    $instanceId = 54321;

    $errorBody = json_encode(['error' => 'Couldn\'t find Event with id 12345']);
    $response = new Response(404, [], Utils::streamFor($errorBody));
    $exception = new ClientException(
      'Not Found',
      new Request('GET', "https://events.stanford.edu/api/2/events/$sourceId"),
      $response
    );

    $this->httpClient->expects($this->once())
      ->method('get')
      ->with("https://events.stanford.edu/api/2/events/$sourceId", ['timeout' => 5])
      ->willThrowException($exception);

    $node = $this->createMock(NodeInterface::class);
    $node->expects($this->once())
      ->method('delete');

    $storage = $this->createMock(EntityStorageInterface::class);
    $storage->expects($this->once())
      ->method('load')
      ->with($destId)
      ->willReturn($node);

    $this->entityTypeManager->expects($this->once())
      ->method('getStorage')
      ->with('node')
      ->willReturn($storage);

    $this->queueWorker->processItem([$sourceId, $destId, $instanceId]);
  }

  /**
   * Tests processItem when a ClientException occurs with different error.
   */
  public function testProcessItemClientExceptionOtherError(): void {
    $sourceId = 12345;
    $destId = 67890;
    $instanceId = 54321;

    $errorBody = json_encode(['error' => 'Some other error message']);
    $response = new Response(400, [], Utils::streamFor($errorBody));
    $exception = new ClientException(
      'Bad Request',
      new Request('GET', "https://events.stanford.edu/api/2/events/$sourceId"),
      $response
    );

    $this->httpClient->expects($this->once())
      ->method('get')
      ->with("https://events.stanford.edu/api/2/events/$sourceId", ['timeout' => 5])
      ->willThrowException($exception);

    $this->entityTypeManager->expects($this->never())
      ->method('getStorage');

    $this->queueWorker->processItem([$sourceId, $destId, $instanceId]);
  }

  /**
   * Tests processItem when ClientException has no error key.
   */
  public function testProcessItemClientExceptionNoErrorKey(): void {
    $sourceId = 12345;
    $destId = 67890;
    $instanceId = 54321;

    $errorBody = json_encode(['message' => 'Different error structure']);
    $response = new Response(500, [], Utils::streamFor($errorBody));
    $exception = new ClientException(
      'Server Error',
      new Request('GET', "https://events.stanford.edu/api/2/events/$sourceId"),
      $response
    );

    $this->httpClient->expects($this->once())
      ->method('get')
      ->with("https://events.stanford.edu/api/2/events/$sourceId", ['timeout' => 5])
      ->willThrowException($exception);

    $this->entityTypeManager->expects($this->never())
      ->method('getStorage');

    $this->queueWorker->processItem([$sourceId, $destId, $instanceId]);
  }

}
