<?php

namespace Drupal\stanford_person_importer;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Http\ClientFactory;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\taxonomy\TermInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\HandlerStack;
use Sainsburys\Guzzle\Oauth2\GrantType\ClientCredentials;
use Sainsburys\Guzzle\Oauth2\GrantType\GrantTypeBase;
use Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware;

/**
 * Stanford CAP API helper service.
 *
 * @package Drupal\stanford_person_importer
 */
class Cap implements CapInterface {

  use StringTranslationTrait;

  /**
   * CAPx API username.
   *
   * @var string
   */
  protected $clientId;

  /**
   * CAPx API password.
   *
   * @var string
   */
  protected $clientSecret;

  /**
   * Http client factory service.
   *
   * @var \Drupal\Core\Http\ClientFactory
   */
  protected $clientFactory;

  /**
   * OAuth2 middleware that acquires and attaches the access token.
   *
   * @var \Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware|null
   */
  protected $oauthMiddleware;

  /**
   * Guzzle client that authenticates requests with the OAuth2 middleware.
   *
   * @var \GuzzleHttp\ClientInterface|null
   */
  protected $client;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Cache service.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * Database connection service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Database logging service.
   *
   * @var \Drupal\Core\Logger\LoggerChannelInterface
   */
  protected $logger;

  /**
   * Validate the form submission credentials are valid.
   *
   * @param array $element
   *   Password form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state.
   * @param array $form
   *   Complete form render array.
   */
  public static function validateCredentials(array $element, FormStateInterface $form_state, array $form) {
    $username = $form_state->getValue(['su_person_cap_username', 0, 'value']);
    $password = $form_state->getValue(['su_person_cap_password', 0, 'value']);

    // Call the service to test the connection.
    $success = \Drupal::service('stanford_person_importer.cap')
      ->setClientId($username)
      ->setClientSecret($password)
      ->testConnection();
    if (!$success) {
      $form_state->setError($element, 'Invalid CAP credentials.');
    }
  }

  /**
   * Capx constructor.
   *
   * @param \Drupal\Core\Http\ClientFactory $client_factory
   *   Http client factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity Type Manager Service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache
   *   Cache service.
   * @param \Drupal\Core\Database\Connection $database
   *   Database connection service.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $logger_factory
   *   Database logging service.
   */
  public function __construct(ClientFactory $client_factory, EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache, Connection $database, LoggerChannelFactoryInterface $logger_factory) {
    $this->clientFactory = $client_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache;
    $this->database = $database;
    $this->logger = $logger_factory->get('stanford_person_importer');
  }

  /**
   * {@inheritDoc}
   */
  public function setClientId(string $client_id): self {
    $this->clientId = $client_id;
    $this->resetClient();
    return $this;
  }

  /**
   * {@inheritDoc}
   */
  public function setClientSecret(string $secret): self {
    $this->clientSecret = $secret;
    $this->resetClient();
    return $this;
  }

  /**
   * Clear the OAuth client so it's rebuilt with the current credentials.
   */
  protected function resetClient(): void {
    $this->oauthMiddleware = NULL;
    $this->client = NULL;
  }

  /**
   * Get the OAuth2 middleware using the client credentials grant.
   *
   * @return \Sainsburys\Guzzle\Oauth2\Middleware\OAuthMiddleware
   *   OAuth2 middleware.
   */
  protected function getOauthMiddleware(): OAuthMiddleware {
    if (!$this->oauthMiddleware) {
      $token_client = $this->clientFactory->fromOptions();
      $grant_type = new ClientCredentials($token_client, [
        GrantTypeBase::CONFIG_CLIENT_ID => $this->clientId,
        GrantTypeBase::CONFIG_CLIENT_SECRET => $this->clientSecret,
        GrantTypeBase::CONFIG_TOKEN_URL => self::getAuthUrl()
          ->toString(TRUE)
          ->getGeneratedUrl(),
      ]);
      $this->oauthMiddleware = new OAuthMiddleware($token_client, $grant_type);
    }
    return $this->oauthMiddleware;
  }

  /**
   * Get the guzzle client that adds the bearer token to API requests.
   *
   * @return \GuzzleHttp\ClientInterface
   *   Guzzle client.
   */
  protected function getClient(): ClientInterface {
    if (!$this->client) {
      $handler_stack = HandlerStack::create();
      $handler_stack->push($this->getOauthMiddleware()->onBefore(), 'oauth2');
      $this->client = $this->clientFactory->fromOptions([
        'handler' => $handler_stack,
        'auth' => 'oauth2',
      ]);
    }
    return $this->client;
  }

  /**
   * Call the API and return the response.
   *
   * @param \Drupal\Core\Url $url
   *   API Url.
   * @param array $options
   *   Guzzle request options.
   *
   * @return bool|array
   *   Response string or false if failed.
   */
  protected function getApiResponse(Url $url, array $options = []) {
    // Make sure the middleware has a valid token before the request is sent.
    $this->getAccessToken();
    try {
      $response = $this->getClient()->request('GET', $url->toString(TRUE)
        ->getGeneratedUrl(), $options);
    }
    catch (GuzzleException|\Exception $e) {
      $this->cache->delete('cap:access_token');
      $this->resetClient();
      // Most errors originate from the API itself, log the error and let it
      // fall over.
      $this->logger->error('An unexpected error came from the API: %message', ['%message' => $e->getMessage()]);
      throw new \Exception($e->getMessage());
    }
    return $response->getStatusCode() == 200 ? json_decode((string) $response->getBody(), TRUE, 512, JSON_THROW_ON_ERROR) : FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function getOrganizationUrl(array $organizations, bool $children = FALSE): Url {
    $organizations = preg_replace('/[^A-Z,0-9]/', '', strtoupper(implode(',', array_unique($organizations))));
    $query = ['orgCodes' => $organizations];
    if ($children) {
      $query['includeChildren'] = 'true';
    }
    return self::getApiUrl('/profiles/v1', ['query' => $query]);
  }

  /**
   * {@inheritDoc}
   */
  public function getWorkgroupUrl(array $workgroups): Url {
    $workgroups = preg_replace('/[^A-Z,:~\-_0-9]/', '', strtoupper(implode(',', array_unique($workgroups))));
    return self::getApiUrl('/profiles/v1', ['query' => ['privGroups' => $workgroups]]);
  }

  /**
   * {@inheritDoc}
   */
  public function getSunetUrl(array $sunetids): Url {
    $sunetids = array_unique($sunetids);
    $query = ['uids' => implode(',', $sunetids), 'ps' => count($sunetids)];
    return self::getApiUrl('/profiles/v1', ['query' => $query]);
  }

  /**
   * {@inheritDoc}
   */
  public function getTotalProfileCount(Url $url): int {
    $url->mergeOptions(['query' => ['ps' => 1]]);
    $response = $this->getApiResponse($url);
    return (int) ($response['totalCount'] ?? 0);
  }

  /**
   * {@inheritDoc}
   */
  public function testConnection(): bool {
    $this->cache->invalidate('cap:access_token');
    $this->resetClient();
    try {
      return !empty($this->getAccessToken());
    }
    catch (\Throwable $e) {
      $this->logger->error('Unable to connect to CAP Api: %message', ['%message' => $e->getMessage()]);
    }
    return FALSE;
  }

  /**
   * {@inheritDoc}
   */
  public function updateOrganizations(): void {
    $this->insertOrgData($this->getOrgData());
  }

  /**
   * Insert the given organization data into the database.
   *
   * @param array $org_data
   *   Keyed array of organization data.
   * @param \Drupal\taxonomy\TermInterface|null $parent
   *   The organization parent if one exists.
   *
   * @throws \Exception
   */
  protected function insertOrgData(array $org_data, ?TermInterface $parent = NULL): void {
    if (!isset($org_data['orgCodes'])) {
      return;
    }

    $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
    $tids = $term_storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('vid', 'cap_org_codes')
      ->condition('su_cap_org_code', $org_data['orgCodes'], 'IN')
      ->execute();

    if (empty($tids)) {
      /** @var \Drupal\taxonomy\TermInterface $term */
      $term = $term_storage->create([
        'name' => preg_replace('/ \(.*?$/', '', $org_data['name']),
        'vid' => 'cap_org_codes',
        'su_cap_org_code' => $org_data['orgCodes'],
      ]);

      if ($parent) {
        $term->set('parent', $parent->id());
      }
      $term->save();
      $parent = $term;
    }
    else {
      $parent = $term_storage->load(reset($tids));
    }

    if (!empty($org_data['children'])) {
      foreach ($org_data['children'] as $child) {
        $this->insertOrgData($child, $parent);
      }
    }
  }

  /**
   * Get the organization data array from the API.
   *
   * @return array
   *   Keyed array of all organization data.
   */
  protected function getOrgData(): array {
    if ($cache = $this->cache->get('cap:org_data')) {
      return $cache->data;
    }

    // AA00 is the root level of all Stanford.
    $result = $this->getApiResponse(self::getApiUrl('/cap/v1/orgs/AA00'));

    if ($result) {
      $this->cache->set('cap:org_data', $result, time() + 60 * 60 * 24 * 7, [
        'cap',
        'cap:org-data',
      ]);
      return $result;
    }
    return [];
  }

  /**
   * Get the API token for CAP.
   *
   * @return string|null
   *   API Token.
   */
  protected function getAccessToken(): ?string {
    try {
      $middleware = $this->getOauthMiddleware();

      // Share the token across requests by seeding the middleware from cache.
      if ($cache = $this->cache->get('cap:access_token')) {
        $middleware->setAccessToken($cache->data['access_token'], 'Bearer');
        return $cache->data['access_token'];
      }

      $token = $middleware->getAccessToken();
    }
    catch (GuzzleException|\Exception $e) {
      $this->resetClient();
      $this->logger->error('Unable to acquire a CAP API access token: %message', ['%message' => $e->getMessage()]);
      throw new \Exception($e->getMessage());
    }

    if (!$token?->getToken()) {
      $this->resetClient();
      return NULL;
    }

    $data = $token->getData();
    $this->cache->set('cap:access_token', $data, time() + ($data['expires_in'] ?? 3600) - 3600, [
      'cap',
      'cap:token',
    ]);
    return $token->getToken();
  }

  /**
   * Get the API url.
   *
   * @param string|null $path
   *   API path to append.
   * @param array $options
   *   Options to pass into the Url::fromUri() method.
   *
   * @return \Drupal\Core\Url
   *   Full url of the API.
   */
  protected static function getApiUrl(?string $path = NULL, array $options = []): Url {
    return Url::fromUri(Settings::get('CAP_API_URL', self::API_URL) . $path, $options);
  }

  /**
   * Get the OAuth url.
   *
   * @param array $options
   *    Options to pass into the Url::fromUri() method.
   *
   * @return \Drupal\Core\Url
   *   Full url of the OAuth service.
   */
  protected static function getAuthUrl(array $options = []): Url {
    return Url::fromUri(Settings::get('CAP_AUTH_URL', self::AUTH_URL), $options);
  }

}
