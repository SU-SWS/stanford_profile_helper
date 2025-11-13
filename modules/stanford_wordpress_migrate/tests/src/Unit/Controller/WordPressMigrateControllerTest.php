<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Controller;

use Drupal\Core\Messenger\MessengerInterface;
use Drupal\stanford_wordpress_migrate\Controller\WordPressMigrateController;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use function Symfony\Component\String\s;

/**
 * Unit tests for WordPressMigrateController.
 */
class WordPressMigrateControllerTest extends UnitTestCase {

  /**
   * The controller under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Controller\WordPressMigrateController
   */
  protected $controller;

  /**
   * Mock messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $messenger;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->messenger = $this->createMock(MessengerInterface::class);

    $this->controller = $this->getMockBuilder(WordPressMigrateController::class)
      ->onlyMethods(['messenger', 'redirect', 't'])
      ->getMock();

    $this->controller->expects($this->any())
      ->method('messenger')
      ->willReturn($this->messenger);

    $this->controller->expects($this->any())
      ->method('t')
      ->willReturnCallback(function($string) {
        return $string;
      });
  }

  /**
   * Test performOperation method with enable operation.
   */
  public function testPerformOperationEnable(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->once())
      ->method('enable')
      ->willReturnSelf();
    $migration->expects($this->once())
      ->method('save');

    $this->messenger->expects($this->once())
      ->method('addStatus')
      ->with('The migration settings have been updated.');

    $this->controller->expects($this->once())
      ->method('redirect')
      ->with('entity.wordpress_migration.collection')
      ->willReturn(new RedirectResponse('/redirect-url'));

    $result = $this->controller->performOperation($migration, 'enable');
    $this->assertInstanceOf(RedirectResponse::class, $result);
  }

  /**
   * Test performOperation method with disable operation.
   */
  public function testPerformOperationDisable(): void {
    $migration = $this->createMock(WordPressMigrationInterface::class);
    $migration->expects($this->once())
      ->method('disable')
      ->willReturnSelf();
    $migration->expects($this->once())
      ->method('save');

    $this->messenger->expects($this->once())
      ->method('addStatus')
      ->with('The migration settings have been updated.');

    $this->controller->expects($this->once())
      ->method('redirect')
      ->with('entity.wordpress_migration.collection')
      ->willReturn(new RedirectResponse('/redirect-url'));

    $result = $this->controller->performOperation($migration, 'disable');
    $this->assertInstanceOf(RedirectResponse::class, $result);
  }

  /**
   * Test handleSourcesAutocomplete method.
   */
  public function testHandleSourcesAutocomplete(): void {
    $request = new Request([
      'q' => 'test',
      'sources' => ['foo', 'bar', 'baz', 'test foo bar'],
    ]);

    // Since the method is complex, we'll just test it returns JsonResponse
    $result = $this->controller->handleSourcesAutocomplete($request);
    $this->assertInstanceOf(JsonResponse::class, $result);

    $data = json_decode($result->getContent(), TRUE);
    $expected = [['value' => 'test foo bar', 'label' => 'test foo bar']];
    $this->assertEquals($expected, $data);
  }

  /**
   * Test handleSourcesAutocomplete method with empty sources.
   */
  public function testHandleSourcesAutocompleteEmptySources(): void {
    $request = new Request(['q' => 'foo']);

    $result = $this->controller->handleSourcesAutocomplete($request);
    $this->assertInstanceOf(JsonResponse::class, $result);

    $data = json_decode($result->getContent(), TRUE);
    $this->assertEquals([], $data);
  }

  /**
   * Test handleSourcesAutocomplete method with empty query.
   */
  public function testHandleSourcesAutocompleteEmpty(): void {
    $request = new Request(['q' => '', 'sources' => ['foo']]);

    $result = $this->controller->handleSourcesAutocomplete($request);
    $this->assertInstanceOf(JsonResponse::class, $result);

    $data = json_decode($result->getContent(), TRUE);
    $this->assertEquals([['value' => 'foo', 'label' => 'foo']], $data);
  }

}
