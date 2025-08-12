<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Kernel\EventSubscriber;

use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Tests\stanford_profile_helper\Kernel\SuProfileHelperKernelTestBase;

/**
 * Hooks that are at the page level.
 */
class PageHooksTest extends SuProfileHelperKernelTestBase {

  public function testPageAttachments() {
    $attachments = [];
    $this->container->get('module_handler')->invokeAllWith(
      'page_attachments',
      function(callable $hook, string $module) use (&$attachments) {
        $hook($attachments);
      }
    );
    $this->assertFalse(in_array('stanford_profile_helper/siteimprove.analytics', $attachments['#attached']['library']));

    putenv('AH_SITE_ENVIRONMENT=prod');
    $attachments = [];
    $this->container->get('module_handler')->invokeAllWith(
      'page_attachments',
      function(callable $hook, string $module) use (&$attachments) {
        $hook($attachments);
      }
    );
    $this->assertTrue(in_array('stanford_profile_helper/siteimprove.analytics', $attachments['#attached']['library']));
    $this->assertArrayNotHasKey('drupalSettings', $attachments['#attached']);

    $user = $this->container->get('entity_type.manager')
      ->getStorage('user')
      ->create(['name' => 'foobar']);
    $user->save();

    //    $this->container->get('current_user')->set
    $account = $this->createMock(AccountProxyInterface::class);
    $account->method('getEmail')->willReturn('foo@bar.com');
    $account->method('getAccountName')->willReturn('foo');
    $account->method('id')->willReturn($user->id());

    $this->container->get('current_user')->setAccount($account);

    $attachments = [];
    $this->container->get('module_handler')->invokeAllWith(
      'page_attachments',
      function(callable $hook, string $module) use (&$attachments) {
        $hook($attachments);
      }
    );
    $this->assertEquals([
      'email' => 'foo@bar.com',
      'name' => 'foo',
      'displayName' => 'foo',
    ], $attachments['#attached']['drupalSettings']['user']);
  }

}
