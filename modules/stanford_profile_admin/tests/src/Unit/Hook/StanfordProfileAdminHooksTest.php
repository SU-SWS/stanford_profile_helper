<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_admin\Unit\Hook;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\stanford_profile_admin\Hook\StanfordProfileAdminHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for StanfordProfileAdminHooks.
 */
#[Group('stanford_profile_admin')]
#[CoversClass(StanfordProfileAdminHooks::class)]
class StanfordProfileAdminHooksTest extends UnitTestCase {

  /**
   * The hooks class under test.
   *
   * @var \Drupal\stanford_profile_admin\Hook\StanfordProfileAdminHooks
   */
  protected StanfordProfileAdminHooks $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new StanfordProfileAdminHooks();
  }

  /**
   * A link that is not routed at all is left untouched, and the route name
   * is never even inspected.
   */
  public function testLinkAlterNotRouted(): void {
    $url = $this->createMock(Url::class);
    $url->method('isRouted')->willReturn(FALSE);
    $url->expects($this->never())->method('getRouteName');

    $variables = ['url' => $url, 'text' => 'Original'];
    $this->hooks->linkAlter($variables);

    $this->assertSame('Original', $variables['text']);
  }

  /**
   * A routed link that doesn't match either target route is untouched.
   */
  public function testLinkAlterRoutedNonMatchingRoute(): void {
    $url = $this->createMock(Url::class);
    $url->method('isRouted')->willReturn(TRUE);
    $url->method('getRouteName')->willReturn('some.other.route');

    $variables = ['url' => $url, 'text' => 'Original'];
    $this->hooks->linkAlter($variables);

    $this->assertSame('Original', $variables['text']);
  }

  /**
   * The entity.user.collection route text gets changed to 'Users'.
   */
  public function testLinkAlterUserCollectionRoute(): void {
    $url = $this->createMock(Url::class);
    $url->method('isRouted')->willReturn(TRUE);
    $url->method('getRouteName')->willReturn('entity.user.collection');

    $variables = ['url' => $url, 'text' => 'Original'];
    $this->hooks->linkAlter($variables);

    $this->assertSame('Users', $variables['text']);
  }

  /**
   * The user.admin_index route text gets changed to 'Users'.
   */
  public function testLinkAlterUserAdminIndexRoute(): void {
    $url = $this->createMock(Url::class);
    $url->method('isRouted')->willReturn(TRUE);
    $url->method('getRouteName')->willReturn('user.admin_index');

    $variables = ['url' => $url, 'text' => 'Original'];
    $this->hooks->linkAlter($variables);

    $this->assertSame('Users', $variables['text']);
  }

  /**
   * The menu link content form gets the library attached.
   */
  public function testFormMenuLinkContentFormAlter(): void {
    $form_state = $this->createMock(FormStateInterface::class);
    $form = [];

    $this->hooks->formMenuLinkContentFormAlter($form, $form_state, 'menu_link_content_form');

    $this->assertSame(
      ['stanford_profile_admin/menu_link_form'],
      $form['#attached']['library']
    );
  }

}
