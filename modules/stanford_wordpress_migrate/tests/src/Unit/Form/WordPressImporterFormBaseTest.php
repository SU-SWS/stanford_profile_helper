<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Unit\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_wordpress_migrate\Form\WordPressImporterFormBase;
use Drupal\stanford_wordpress_migrate\WordPressMigrationInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\ClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Unit tests for WordPressImporterFormBase.
 */
class WordPressImporterFormBaseTest extends UnitTestCase {

  /**
   * The form under test.
   *
   * @var \Drupal\stanford_wordpress_migrate\Form\WordPressImporterFormBase
   */
  protected $form;

  /**
   * Mock HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $httpClient;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->httpClient = $this->createMock(ClientInterface::class);

    // Create anonymous class to test abstract base class
    $this->form = new class($this->httpClient) extends WordPressImporterFormBase {

      public function getFormId() {
        return 'test_form';
      }

      public function submitForm(array &$form, FormStateInterface $form_state): void {
        // Implementation not needed for testing
      }

    };
  }

  /**
   * Test form construction.
   */
  public function testConstruct(): void {
    $this->assertInstanceOf(WordPressImporterFormBase::class, $this->form);
  }

  /**
   * Test create method.
   */
  public function testCreate(): void {
    $container = $this->createMock(ContainerInterface::class);
    $container->expects($this->once())
      ->method('get')
      ->with('http_client')
      ->willReturn($this->httpClient);

    $form = new class($this->httpClient) extends WordPressImporterFormBase {

      public function getFormId() {
        return 'test_form';
      }

      public function submitForm(array &$form, FormStateInterface $form_state): void {
        // Implementation not needed for testing
      }

      public static function create(ContainerInterface $container) {
        return parent::create($container);
      }

    };

    $result = $form::create($container);
    $this->assertInstanceOf(WordPressImporterFormBase::class, $result);
  }

  /**
   * Test buildForm method without API routes.
   */
  public function testBuildFormWithoutApiRoutes(): void {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $migration = $this->createMock(WordPressMigrationInterface::class);

    $cached_values = ['wordpress_migration' => $migration];

    $form_state->method('getTemporaryValue')
      ->willReturnMap([
        [['wizard'], $cached_values],
        [['wizard', 'api-routes'], NULL],
      ]);

    $migration->expects($this->once())
      ->method('getBaseUrl')
      ->willReturn('https://example.com');

    $form_state->expects($this->once())
      ->method('setTemporaryValue')
      ->with(['wizard', 'api-routes'], $this->isType('array'));

    // Create a mock form that extends the base class
    $mockForm = $this->getMockBuilder(WordPressImporterFormBase::class)
      ->setConstructorArgs([$this->httpClient])
      ->onlyMethods(['getApiEndpoints'])
      ->getMock();

    $mockForm->expects($this->once())
      ->method('getApiEndpoints')
      ->with('https://example.com')
      ->willReturn(['test' => 'endpoint']);

    $result = $mockForm->buildForm($form, $form_state);
    $this->assertIsArray($result);
  }

  /**
   * Test buildForm method with existing API routes.
   */
  public function testBuildFormWithExistingApiRoutes(): void {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);

    $cached_values = ['wordpress_migration' => $this->createMock(WordPressMigrationInterface::class)];
    $existing_routes = ['existing' => 'route'];

    $form_state->method('getTemporaryValue')
      ->willReturnMap([
        [['wizard'], $cached_values],
        [['wizard', 'api-routes'], $existing_routes],
      ]);

    // Should not call setTemporaryValue if routes already exist
    $form_state->expects($this->never())
      ->method('setTemporaryValue');

    $result = $this->form->buildForm($form, $form_state);
    $this->assertIsArray($result);
  }

  /**
   * Test buildForm method without base URL.
   */
  public function testBuildFormWithoutBaseUrl(): void {
    $form = [];
    $form_state = $this->createMock(FormStateInterface::class);
    $migration = $this->createMock(WordPressMigrationInterface::class);

    $cached_values = ['wordpress_migration' => $migration];

    $form_state->method('getTemporaryValue')
      ->willReturnMap([
        [['wizard'], $cached_values],
        [['wizard', 'api-routes'], NULL],
      ]);

    $migration->expects($this->once())
      ->method('getBaseUrl')
      ->willReturn(NULL);

    // Should not call setTemporaryValue if no base URL
    $form_state->expects($this->never())
      ->method('setTemporaryValue');

    $result = $this->form->buildForm($form, $form_state);
    $this->assertIsArray($result);
  }

}
