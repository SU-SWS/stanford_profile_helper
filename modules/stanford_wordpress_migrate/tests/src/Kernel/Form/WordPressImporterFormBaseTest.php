<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_wordpress_migrate\Kernel\Form;

use Drupal\Core\Form\FormState;
use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_wordpress_migrate\Entity\WordPressMigration;
use Drupal\stanford_wordpress_migrate\Form\WordPressImporterFormBase;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

/**
 * Kernel tests for WordPressImporterFormBase.
 */
class WordPressImporterFormBaseTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'field',
    'user',
    'migrate',
    'stanford_wordpress_migrate',
  ];

  /**
   * Test form instance.
   *
   * @var \Drupal\stanford_wordpress_migrate\Form\WordPressImporterFormBase
   */
  protected $form;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('user');
    $this->installEntitySchema('wordpress_migration');

    // Create a concrete implementation for testing the abstract class.
    $this->form = new class($this->container->get('http_client')) extends WordPressImporterFormBase {

      public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
        return parent::buildForm($form, $form_state);
      }

    };
  }

  /**
   * Test form ID.
   */
  public function testGetFormId(): void {
    $this->assertEquals('wordpress_importer_form', $this->form->getFormId());
  }

  /**
   * Test buildForm without API routes.
   */
  public function testBuildFormWithoutApiRoutes(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => '',
    ]);

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], ['wordpress_migration' => $migration]);

    $form = $this->form->buildForm([], $form_state);

    $this->assertIsArray($form);
    $this->assertNull($form_state->getTemporaryValue(['wizard', 'api-routes']));
  }

  /**
   * Test buildForm with API routes.
   */
  public function testBuildFormWithApiRoutes(): void {
    $migration = WordPressMigration::create([
      'label' => 'Test Migration',
      'base_url' => 'https://example.com',
    ]);

    // Mock the HTTP client response.
    $mockResponse = new Response(200, [], json_encode([
      'routes' => [
        '/wp/v2/posts' => [],
        '/wp/v2/pages' => [],
        '/wp/v2/categories' => [],
      ],
    ]));

    $mock = new MockHandler([$mockResponse]);
    $handlerStack = HandlerStack::create($mock);
    $mockClient = new Client(['handler' => $handlerStack]);

    $form = new class($mockClient) extends WordPressImporterFormBase {

      public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
        return parent::buildForm($form, $form_state);
      }

    };

    $form_state = new FormState();
    $form_state->setTemporaryValue(['wizard'], ['wordpress_migration' => $migration]);

    $result = $form->buildForm([], $form_state);

    $this->assertIsArray($result);
    $apiRoutes = $form_state->getTemporaryValue(['wizard', 'api-routes']);
    $this->assertNotEmpty($apiRoutes);
  }

  /**
   * Test getApiEndpoints method.
   */
  public function testGetApiEndpoints(): void {
    $mockResponse = new Response(200, [], json_encode([
      'routes' => [
        '/wp/v2/posts' => [],
        '/wp/v2/pages' => [],
        '/wp/v2/categories' => [],
        '/wp/v2/invalid-route/something' => [],
      ],
    ]));

    $mock = new MockHandler([$mockResponse]);
    $handlerStack = HandlerStack::create($mock);
    $mockClient = new Client(['handler' => $handlerStack]);

    $form = new class($mockClient) extends WordPressImporterFormBase {

      public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
        return parent::buildForm($form, $form_state);
      }

      public function testGetApiEndpoints($baseUrl) {
        return $this->getApiEndpoints($baseUrl);
      }

    };

    $endpoints = $form->testGetApiEndpoints('https://example.com');
    $this->assertIsArray($endpoints);
    $this->assertArrayHasKey('/wp/v2/posts', $endpoints);
    $this->assertEquals('Posts', $endpoints['/wp/v2/posts']);
  }

  /**
   * Test getApiEndpoints with exception.
   */
  public function testGetApiEndpointsWithException(): void {
    $mockResponse = new Response(500);

    $mock = new MockHandler([$mockResponse]);
    $handlerStack = HandlerStack::create($mock);
    $mockClient = new Client(['handler' => $handlerStack]);

    $form = new class($mockClient) extends WordPressImporterFormBase {

      public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {
        return parent::buildForm($form, $form_state);
      }

      public function testGetApiEndpoints($baseUrl) {
        return $this->getApiEndpoints($baseUrl);
      }

    };

    $endpoints = $form->testGetApiEndpoints('https://example.com');
    $this->assertEquals([], $endpoints);
  }

  /**
   * Test addAnotherAjax method.
   */
  public function testAddAnotherAjax(): void {
    $form = [
      'test_element' => [
        '#type' => 'textfield',
      ],
    ];

    $form_state = new FormState();
    $form_state->setTriggeringElement([
      '#add-more' => 'test_element',
    ]);

    $result = WordPressImporterFormBase::addAnotherAjax($form, $form_state);
    $this->assertEquals($form['test_element'], $result);
  }

  /**
   * Test addAnother method.
   */
  public function testAddAnother(): void {
    $form = [];
    $form_state = new FormState();
    $form_state->set('num_mappings', 5);

    WordPressImporterFormBase::addAnother($form, $form_state);

    $this->assertEquals(6, $form_state->get('num_mappings'));
    $this->assertTrue($form_state->isRebuilding());
  }

}
