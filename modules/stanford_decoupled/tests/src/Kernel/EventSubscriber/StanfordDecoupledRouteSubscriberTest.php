<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_decoupled\Kernel\EventSubscriber;

use Drupal\KernelTests\KernelTestBase;
use Drupal\stanford_decoupled\EventSubscriber\StanfordDecoupledRouteSubscriber;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Tests for StanfordDecoupledRouteSubscriber.
 */
#[CoversClass(StanfordDecoupledRouteSubscriber::class)]
#[Group('stanford_decoupled')]
class StanfordDecoupledRouteSubscriberTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'user',
    'basic_auth',
    'stanford_decoupled',
    'entity_usage',
    'next',
  ];

  /**
   * Test route alteration when NOT in decoupled mode.
   *
   * // Covers: alterRoutes
   * // Covers: __construct
   */
  public function testRouteAlterNotDecoupled(): void {
    // Ensure decoupled mode is disabled.
    \Drupal::cache()->set('stanford_decoupled', FALSE);

    // Create a route subscriber with authentication providers.
    $auth_providers = [
      'basic_auth' => 'Basic Auth Provider',
      'cookie' => 'Cookie Provider',
    ];
    $subscriber = new StanfordDecoupledRouteSubscriber($auth_providers);

    // Create test routes.
    $collection = new RouteCollection();
    $collection->add('system.files', new Route('/system/files/{scheme}'));
    $collection->add('system.private_file_download', new Route('/system/files/{filepath}'));

    // Alter routes.
    $reflection = new \ReflectionClass($subscriber);
    $method = $reflection->getMethod('alterRoutes');
    $method->setAccessible(TRUE);
    $method->invoke($subscriber, $collection);

    // Routes should NOT have _auth options when not decoupled.
    $this->assertNull($collection->get('system.files')->getOption('_auth'));
    $this->assertNull($collection->get('system.private_file_download')->getOption('_auth'));
  }

  /**
   * Test route alteration when in decoupled mode.
   *
   * // Covers: alterRoutes
   * // Covers: __construct
   */
  public function testRouteAlterDecoupled(): void {
    // Enable decoupled mode.
    \Drupal::cache()->set('stanford_decoupled', TRUE);

    // Create a route subscriber with authentication providers.
    $auth_providers = [
      'basic_auth' => 'Basic Auth Provider',
      'cookie' => 'Cookie Provider',
    ];
    $subscriber = new StanfordDecoupledRouteSubscriber($auth_providers);

    // Create test routes.
    $collection = new RouteCollection();
    $collection->add('system.files', new Route('/system/files/{scheme}'));
    $collection->add('system.private_file_download', new Route('/system/files/{filepath}'));

    // Alter routes.
    $reflection = new \ReflectionClass($subscriber);
    $method = $reflection->getMethod('alterRoutes');
    $method->setAccessible(TRUE);
    $method->invoke($subscriber, $collection);

    // Routes should have _auth options when decoupled.
    $this->assertNotNull($collection->get('system.files')->getOption('_auth'));
    $this->assertNotNull($collection->get('system.private_file_download')->getOption('_auth'));

    // Verify the auth providers are set correctly.
    $this->assertEquals(['basic_auth', 'cookie'], $collection->get('system.files')->getOption('_auth'));
    $this->assertEquals(['basic_auth', 'cookie'], $collection->get('system.private_file_download')->getOption('_auth'));
  }

  /**
   * Test constructor stores provider IDs correctly.
   *
   * // Covers: __construct
   */
  public function testConstructor(): void {
    $auth_providers = [
      'basic_auth' => 'Basic Auth Provider',
      'cookie' => 'Cookie Provider',
      'oauth2' => 'OAuth2 Provider',
    ];

    $subscriber = new StanfordDecoupledRouteSubscriber($auth_providers);

    $reflection = new \ReflectionClass($subscriber);
    $property = $reflection->getProperty('providerIds');
    $property->setAccessible(TRUE);

    $this->assertEquals(['basic_auth', 'cookie', 'oauth2'], $property->getValue($subscriber));
  }

  /**
   * Test with empty authentication providers.
   *
   * // Covers: alterRoutes
   * // Covers: __construct
   */
  public function testAlterRoutesWithEmptyProviders(): void {
    // Enable decoupled mode.
    \Drupal::cache()->set('stanford_decoupled', TRUE);

    // Create subscriber with no auth providers.
    $subscriber = new StanfordDecoupledRouteSubscriber([]);

    // Create test routes.
    $collection = new RouteCollection();
    $collection->add('system.files', new Route('/system/files/{scheme}'));
    $collection->add('system.private_file_download', new Route('/system/files/{filepath}'));

    // Alter routes.
    $reflection = new \ReflectionClass($subscriber);
    $method = $reflection->getMethod('alterRoutes');
    $method->setAccessible(TRUE);
    $method->invoke($subscriber, $collection);

    // Routes should have _auth options but with empty array.
    $this->assertEquals([], $collection->get('system.files')->getOption('_auth'));
    $this->assertEquals([], $collection->get('system.private_file_download')->getOption('_auth'));
  }

  /**
   * Test with single authentication provider.
   *
   * // Covers: alterRoutes
   * // Covers: __construct
   */
  public function testAlterRoutesWithSingleProvider(): void {
    // Enable decoupled mode.
    \Drupal::cache()->set('stanford_decoupled', TRUE);

    // Create subscriber with single auth provider.
    $subscriber = new StanfordDecoupledRouteSubscriber(['basic_auth' => 'Basic Auth']);

    // Create test routes.
    $collection = new RouteCollection();
    $collection->add('system.files', new Route('/system/files/{scheme}'));
    $collection->add('system.private_file_download', new Route('/system/files/{filepath}'));

    // Alter routes.
    $reflection = new \ReflectionClass($subscriber);
    $method = $reflection->getMethod('alterRoutes');
    $method->setAccessible(TRUE);
    $method->invoke($subscriber, $collection);

    // Routes should have _auth option with single provider.
    $this->assertEquals(['basic_auth'], $collection->get('system.files')->getOption('_auth'));
    $this->assertEquals(['basic_auth'], $collection->get('system.private_file_download')->getOption('_auth'));
  }

  /**
   * Test that only specific routes are altered.
   *
   * // Covers: alterRoutes
   */
  public function testOnlySpecificRoutesAltered(): void {
    // Enable decoupled mode.
    \Drupal::cache()->set('stanford_decoupled', TRUE);

    $auth_providers = ['basic_auth' => 'Basic Auth'];
    $subscriber = new StanfordDecoupledRouteSubscriber($auth_providers);

    // Create test routes including some that shouldn't be altered.
    $collection = new RouteCollection();
    $collection->add('system.files', new Route('/system/files/{scheme}'));
    $collection->add('system.private_file_download', new Route('/system/files/{filepath}'));
    $collection->add('system.admin', new Route('/admin'));
    $collection->add('user.login', new Route('/user/login'));

    // Alter routes.
    $reflection = new \ReflectionClass($subscriber);
    $method = $reflection->getMethod('alterRoutes');
    $method->setAccessible(TRUE);
    $method->invoke($subscriber, $collection);

    // Only system.files and system.private_file_download should have _auth.
    $this->assertNotNull($collection->get('system.files')->getOption('_auth'));
    $this->assertNotNull($collection->get('system.private_file_download')->getOption('_auth'));
    $this->assertNull($collection->get('system.admin')->getOption('_auth'));
    $this->assertNull($collection->get('user.login')->getOption('_auth'));
  }

  /**
   * Test with missing routes.
   *
   * // Covers: alterRoutes
   */
  public function testAlterRoutesWithMissingRoutes(): void {
    // Enable decoupled mode.
    \Drupal::cache()->set('stanford_decoupled', TRUE);

    $auth_providers = ['basic_auth' => 'Basic Auth'];
    $subscriber = new StanfordDecoupledRouteSubscriber($auth_providers);

    // Create collection with only one of the expected routes.
    $collection = new RouteCollection();
    $collection->add('system.files', new Route('/system/files/{scheme}'));

    // Alter routes - should not throw exception for missing route.
    $reflection = new \ReflectionClass($subscriber);
    $method = $reflection->getMethod('alterRoutes');
    $method->setAccessible(TRUE);
    $method->invoke($subscriber, $collection);

    // Only the existing route should be altered.
    $this->assertEquals(['basic_auth'], $collection->get('system.files')->getOption('_auth'));
  }

  /**
   * Test route collection with no routes.
   *
   * // Covers: alterRoutes
   */
  public function testAlterRoutesWithEmptyCollection(): void {
    // Enable decoupled mode.
    \Drupal::cache()->set('stanford_decoupled', TRUE);

    $auth_providers = ['basic_auth' => 'Basic Auth'];
    $subscriber = new StanfordDecoupledRouteSubscriber($auth_providers);

    // Empty route collection.
    $collection = new RouteCollection();

    // Alter routes - should not throw exception.
    $reflection = new \ReflectionClass($subscriber);
    $method = $reflection->getMethod('alterRoutes');
    $method->setAccessible(TRUE);
    $method->invoke($subscriber, $collection);

    // Collection should still be empty.
    $this->assertCount(0, $collection);
  }

  /**
   * Test that existing auth options are replaced.
   *
   * // Covers: alterRoutes
   */
  public function testExistingAuthOptionsReplaced(): void {
    // Enable decoupled mode.
    \Drupal::cache()->set('stanford_decoupled', TRUE);

    $auth_providers = ['basic_auth' => 'Basic Auth', 'cookie' => 'Cookie'];
    $subscriber = new StanfordDecoupledRouteSubscriber($auth_providers);

    // Create routes with existing auth options.
    $collection = new RouteCollection();
    $route1 = new Route('/system/files/{scheme}');
    $route1->addOptions(['_auth' => ['old_provider']]);
    $collection->add('system.files', $route1);

    $route2 = new Route('/system/files/{filepath}');
    $route2->addOptions(['_auth' => ['another_old_provider']]);
    $collection->add('system.private_file_download', $route2);

    // Alter routes.
    $reflection = new \ReflectionClass($subscriber);
    $method = $reflection->getMethod('alterRoutes');
    $method->setAccessible(TRUE);
    $method->invoke($subscriber, $collection);

    // Auth options should be replaced with new values.
    $this->assertEquals(['basic_auth', 'cookie'], $collection->get('system.files')->getOption('_auth'));
    $this->assertEquals(['basic_auth', 'cookie'], $collection->get('system.private_file_download')->getOption('_auth'));
  }

  /**
   * Test that other route options are preserved.
   *
   * // Covers: alterRoutes
   */
  public function testOtherRouteOptionsPreserved(): void {
    // Enable decoupled mode.
    \Drupal::cache()->set('stanford_decoupled', TRUE);

    $auth_providers = ['basic_auth' => 'Basic Auth'];
    $subscriber = new StanfordDecoupledRouteSubscriber($auth_providers);

    // Create route with other options.
    $collection = new RouteCollection();
    $route = new Route('/system/files/{scheme}');
    $route->addOptions([
      '_admin_route' => TRUE,
      'parameters' => ['scheme' => []],
    ]);
    $collection->add('system.files', $route);

    // Alter routes.
    $reflection = new \ReflectionClass($subscriber);
    $method = $reflection->getMethod('alterRoutes');
    $method->setAccessible(TRUE);
    $method->invoke($subscriber, $collection);

    // Other options should be preserved.
    $this->assertTrue($collection->get('system.files')->getOption('_admin_route'));
    $this->assertNotNull($collection->get('system.files')->getOption('parameters'));
    $this->assertEquals(['basic_auth'], $collection->get('system.files')->getOption('_auth'));
  }

  /**
   * Test with complex authentication provider array.
   *
   * // Covers: __construct
   * // Covers: alterRoutes
   */
  public function testComplexAuthenticationProviders(): void {
    // Enable decoupled mode.
    \Drupal::cache()->set('stanford_decoupled', TRUE);

    // Complex provider array with various keys.
    $auth_providers = [
      'basic_auth' => ['id' => 'basic_auth', 'label' => 'Basic Auth'],
      'cookie' => ['id' => 'cookie', 'label' => 'Cookie'],
      'oauth2' => ['id' => 'oauth2', 'label' => 'OAuth2'],
      'jwt_auth' => ['id' => 'jwt_auth', 'label' => 'JWT Auth'],
    ];

    $subscriber = new StanfordDecoupledRouteSubscriber($auth_providers);

    // Create test routes.
    $collection = new RouteCollection();
    $collection->add('system.files', new Route('/system/files/{scheme}'));
    $collection->add('system.private_file_download', new Route('/system/files/{filepath}'));

    // Alter routes.
    $reflection = new \ReflectionClass($subscriber);
    $method = $reflection->getMethod('alterRoutes');
    $method->setAccessible(TRUE);
    $method->invoke($subscriber, $collection);

    // Should extract keys from auth providers.
    $expected_providers = ['basic_auth', 'cookie', 'oauth2', 'jwt_auth'];
    $this->assertEquals($expected_providers, $collection->get('system.files')->getOption('_auth'));
    $this->assertEquals($expected_providers, $collection->get('system.private_file_download')->getOption('_auth'));
  }

}
