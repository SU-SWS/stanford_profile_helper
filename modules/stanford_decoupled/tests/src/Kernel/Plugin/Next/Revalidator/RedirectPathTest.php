<?php

namespace Drupal\Tests\stanford_decoupled\Kernel\Plugin\Next\Revalidator;

use Drupal\KernelTests\KernelTestBase;
use Drupal\next\Entity\NextSite;
use Drupal\next\Event\EntityActionEvent;
use Drupal\node\NodeInterface;
use Drupal\redirect\Entity\Redirect;
use Drupal\user\Entity\User;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;

class RedirectPathTest extends KernelTestBase {

  protected static $modules = [
    'system',
    'user',
    'next',
    'redirect',
    'path_alias',
    'link',
    'stanford_decoupled',
  ];

  protected $responseCode = 404;

  protected function setUp(): void {
    parent::setUp();
    $this->installEntitySchema('redirect');

    $response = $this->createMock(ResponseInterface::class);
    $response->method('getStatusCode')
      ->willReturnReference($this->responseCode);

    $client = $this->createMock(ClientInterface::class);
    $client->method('request')->willReturn($response);
    \Drupal::getContainer()->set('http_client', $client);
    \Drupal::configFactory()
      ->getEditable('next.settings')
      ->set('debug', TRUE)
      ->save();
  }

  public function testRevalidationNonRedirect() {
    /** @var \Drupal\stanford_decoupled\Plugin\Next\Revalidator\RedirectPath $plugin */
    $plugin = $this->container->get('plugin.manager.next.revalidator')
      ->createInstance('redirect_path');
    $this->assertEmpty($plugin->defaultConfiguration());

    $user = User::create([]);
    // No next sites configured.
    $event = new EntityActionEvent($user, 'update', [], '');
    $this->assertFalse($plugin->revalidate($event));

    $next_site = NextSite::create([
      'label' => 'Blog',
      'id' => 'blog',
      'base_url' => 'https://blog.com',
    ]);
    $event = new EntityActionEvent($user, 'update', [$next_site], '');
    $this->assertFalse($plugin->revalidate($event));
  }

  public function testRevalidation() {
    /** @var \Drupal\stanford_decoupled\Plugin\Next\Revalidator\RedirectPath $plugin */
    $plugin = $this->container->get('plugin.manager.next.revalidator')
      ->createInstance('redirect_path');

    $redirect = Redirect::create(['status_code' => '301']);
    $redirect->setSource('/foo');
    $redirect->setRedirect('/node--0');
    $redirect->setLanguage('und');

    $next_site = NextSite::create([
      'label' => 'Blog',
      'id' => 'blog',
      'base_url' => 'https://blog.com',
    ]);
    // No revalidation url configured, an error will be throw. Test the catch.
    $event = new EntityActionEvent($redirect, 'update', [$next_site], '');
    $this->assertFalse($plugin->revalidate($event));

    $next_site->setRevalidateUrl('http://localhost');
    $next_site->setRevalidateSecret('foobar');
    $event = new EntityActionEvent($redirect, 'update', [$next_site], '');
    $this->assertFalse($plugin->revalidate($event));

    $this->responseCode = 200;
    $next_site->setRevalidateUrl('http://localhost');
    $next_site->setRevalidateSecret('foobar');
    $event = new EntityActionEvent($redirect, 'update', [$next_site], '');
    $this->assertTrue($plugin->revalidate($event));
  }

}
