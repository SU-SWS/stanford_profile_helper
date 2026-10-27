<?php

namespace Drupal\Tests\stanford_person_importer\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Form\FormState;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Logger\LoggerChannelInterface;
use Drupal\Core\PathProcessor\OutboundPathProcessorInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Drupal\Core\Utility\UnroutedUrlAssembler;
use Drupal\stanford_person_importer\Cap;
use Drupal\taxonomy\TermInterface;
use Drupal\Tests\UnitTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Promise\Create;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use PHPUnit\Framework\Attributes\Group;

/**
 * Class CapTest.
 */
#[Group('stanford_person_importer')]
class CapTest extends UnitTestCase {

  /**
   * Cap service.
   *
   * @var \Drupal\stanford_person_importer\Cap
   */
  protected $service;

  /**
   * The response code mock guzzle will return.
   *
   * @var int
   */
  protected $guzzleStatusCode = 200;

  /**
   * Response body the mock guzzle will return.
   *
   * @var string
   */
  protected $guzzleBody = '';

  /**
   * Requests sent through the mock guzzle handler.
   *
   * @var \Psr\Http\Message\RequestInterface[]
   */
  protected $requests = [];

  /**
   * The simulated cache data object.
   *
   * @var \stdClass
   */
  protected $cacheData;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $client_factory = $this->createMock(ClientFactory::class);
    $client_factory->method('fromOptions')
      ->willReturnCallback([$this, 'clientFactoryCallback']);

    $entity_type_manager = $this->getEntityTypeManager();

    $cache = $this->createMock(CacheBackendInterface::class);
    $cache->method('get')->willReturnReference($this->cacheData);

    $database = $this->createMock(Connection::class);

    $logger = $this->createMock(LoggerChannelInterface::class);

    $logger_factory = $this->createMock(LoggerChannelFactoryInterface::class);
    $logger_factory->method('get')->wilLReturn($logger);

    $this->service = new Cap($client_factory, $entity_type_manager, $cache, $database, $logger_factory);
    $this->service->setClientId('foo')->setClientSecret('bar');

    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $path_processor = $this->createMock(OutboundPathProcessorInterface::class);
    $unrouted_assembler = new UnroutedUrlAssembler($request_stack, $path_processor);

    $container = new ContainerBuilder();
    $container->set('stanford_person_importer.cap', $this->service);
    $container->set('string_translation', $this->getStringTranslationStub());
    $container->set('unrouted_url_assembler', $unrouted_assembler);
    \Drupal::setContainer($container);
  }

  /**
   * Get a mock entity type manager service.
   */
  protected function getEntityTypeManager() {
    $entity_query = $this->createMock(QueryInterface::class);
    $entity_query->method('accessCheck')->willReturnSelf();
    $entity_query->method('condition')->willReturnSelf();
    $entity_query->method('execute')->willReturn([]);

    $entity = $this->createMock(TermInterface::class);

    $entity_storage = $this->createMock(EntityStorageInterface::class);
    $entity_storage->method('create')->willReturn($entity);
    $entity_storage->method('getQuery')->willReturn($entity_query);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->method('getStorage')->willReturn($entity_storage);

    return $entity_type_manager;
  }

  /**
   * Test the service credentials will work.
   */
  public function testCredentials() {
    $this->guzzleBody = json_encode([
      'expires_in' => 100,
      'access_token' => 'foo-bar-baz',
      'token_type' => 'Bearer',
    ]);
    $success = $this->service->setClientId('foo')
      ->setClientSecret('bar')
      ->testConnection();
    $this->assertTrue($success);

    $this->guzzleStatusCode = 403;
    $this->assertFalse($this->service->testConnection());

    $this->guzzleStatusCode = 0;
    $this->assertFalse($this->service->testConnection());
  }

  /**
   * Cached access token will return successful connection.
   */
  public function testCachedResponses() {
    $this->cacheData = new \stdClass();
    $this->cacheData->data = ['access_token' => 'foo'];
    $this->assertTrue($this->service->testConnection());
  }

  /**
   * Form field validation will give form errors.
   */
  public function testFormValidateCredentials() {
    $element = ['#parents' => []];
    $form_state = new FormState();
    $form = [];

    $form_state->setValues([
      'su_person_cap_username' => [['value' => 'foo']],
      'su_person_cap_password' => [['value' => 'bar']],
    ]);
    $this->guzzleStatusCode = 401;
    $this->service::validateCredentials($element, $form_state, $form);
    $this->assertTrue($form_state::hasAnyErrors());
    $form_state->clearErrors();

    $this->guzzleStatusCode = 200;
    $this->guzzleBody = json_encode([
      'expires_in' => 100,
      'access_token' => 'foo-bar-baz',
      'token_type' => 'Bearer',
    ]);
    $this->service::validateCredentials($element, $form_state, $form);
    $this->assertFalse($form_state::hasAnyErrors());
  }

  /**
   * Returned urls from the service will be properly formatted.
   */
  public function testUrls() {
    $url = urldecode($this->service->getOrganizationUrl(['foo', 'bar'])
      ->toString());
    $this->assertEquals('https://api.stanford.edu/profiles/v1?orgCodes=FOO,BAR', $url);

    $url = urldecode($this->service->getOrganizationUrl(['foo', 'bar'], TRUE)
      ->toString());
    $this->assertEquals('https://api.stanford.edu/profiles/v1?orgCodes=FOO,BAR&includeChildren=true', $url);

    $url = urldecode($this->service->getWorkgroupUrl(['foo:bar_-baz'])
      ->toString());
    $this->assertEquals('https://api.stanford.edu/profiles/v1?privGroups=FOO:BAR_-BAZ', $url);

    $url = urldecode($this->service->getSunetUrl(['foobarbaz'])->toString());
    $this->assertEquals('https://api.stanford.edu/profiles/v1?uids=foobarbaz&ps=1', $url);

    $sunets = array_fill(0, 20, 'foo');
    $url = urldecode($this->service->getSunetUrl($sunets)->toString());
    $this->assertEquals("https://api.stanford.edu/profiles/v1?uids=foo&ps=1", $url);
  }

  /**
   * Profile urls use the API url from settings when it's overridden.
   */
  public function testUrlsSettingsOverride() {
    new Settings(['CAP_API_URL' => 'https://api-test.stanford.edu']);

    $url = urldecode($this->service->getOrganizationUrl(['foo'])->toString());
    $this->assertEquals('https://api-test.stanford.edu/profiles/v1?orgCodes=FOO', $url);

    $url = urldecode($this->service->getWorkgroupUrl(['foo:bar'])->toString());
    $this->assertEquals('https://api-test.stanford.edu/profiles/v1?privGroups=FOO:BAR', $url);

    $url = urldecode($this->service->getSunetUrl(['foo'])->toString());
    $this->assertEquals('https://api-test.stanford.edu/profiles/v1?uids=foo&ps=1', $url);

    new Settings([]);
  }

  /**
   * The api will return an appropriate count.
   */
  public function testProfileCount() {
    $this->guzzleBody = json_encode([
      'totalCount' => 123,
      'expires_in' => 100,
      'access_token' => 'foo',
      'token_type' => 'Bearer',
    ]);
    $this->assertEquals(123, $this->service->getTotalProfileCount(Url::fromUri('http://localhost')));

    // The OAuth middleware attached the bearer token to the API request.
    $api_request = end($this->requests);
    $this->assertEquals('Bearer foo', $api_request->getHeaderLine('Authorization'));

    $this->guzzleBody = NULL;
    $this->expectException(\JsonException::class);
    $this->assertEquals(0, $this->service->getTotalProfileCount(Url::fromUri('http://localhost')));
  }

  /**
   * The org codes will be stored in taxonomy terms.
   */
  public function testUpdateOrgs() {
    $body = [
      'expires_in' => 100,
      'access_token' => 'foo',
      'token_type' => 'Bearer',
      'name' => 'foo',
      'orgCodes' => ['foo', 'bar'],
      'children' => [['name' => 'baz', 'orgCodes' => ['baz']]],
    ];
    $this->guzzleBody = json_encode($body);

    $this->assertNull($this->service->updateOrganizations());

    $this->cacheData = new \stdClass();
    $this->cacheData->data = $body;
    $this->assertNull($this->service->updateOrganizations());

    $this->cacheData = NULL;
    $this->guzzleBody = NULL;
    $this->expectException(\JsonException::class);
    $this->assertNull($this->service->updateOrganizations());
  }

  /**
   * Mock client factory callback that uses the mock guzzle handler.
   */
  public function clientFactoryCallback(array $options = []) {
    $handler_stack = $options['handler'] ?? HandlerStack::create();
    $handler_stack->setHandler([$this, 'guzzleHandlerCallback']);
    $options['handler'] = $handler_stack;
    return new Client($options);
  }

  /**
   * Mock guzzle handler callback.
   */
  public function guzzleHandlerCallback(RequestInterface $request) {
    $this->requests[] = $request;
    if ($this->guzzleStatusCode == 0) {
      throw new \Exception('It failed');
    }
    return Create::promiseFor(new Response($this->guzzleStatusCode, [], $this->guzzleBody));
  }

  /**
   * Retain the numbers in the workgroups and organizations.
   */
  public function testNumbers() {
    $this->assertStringContainsString('FOO:BAR123', urldecode($this->service->getWorkgroupUrl(['foo:bar123'])
      ->toString()));
    $this->assertStringContainsString('FOOBAR123', urldecode($this->service->getOrganizationUrl(['foo:bar123'])
      ->toString()));
  }

}
