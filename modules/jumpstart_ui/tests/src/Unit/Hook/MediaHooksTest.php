<?php

declare(strict_types=1);

namespace Drupal\Tests\jumpstart_ui\Unit\Hook;

use Drupal\jumpstart_ui\Hook\MediaHooks;
use Drupal\media\MediaInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for MediaHooks.
 */
#[Group('jumpstart_ui')]
#[CoversClass(MediaHooks::class)]
class MediaHooksTest extends UnitTestCase {

  /**
   * The hooks class under test.
   */
  protected MediaHooks $hooks;

  /**
   * {@inheritDoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->hooks = new MediaHooks();
  }

  /**
   * The wrapper class and bundle class should be added to the attributes.
   */
  public function testPreprocessMedia(): void {
    $media = $this->createMock(MediaInterface::class);
    $media->method('bundle')->willReturn('image');

    $variables = [
      'attributes' => [],
      'elements' => ['#media' => $media],
    ];

    $this->hooks->preprocessMedia($variables);

    $this->assertSame(
      ['media-entity-wrapper', 'image'],
      $variables['attributes']['class']
    );
  }

  /**
   * A different bundle should be reflected in the class list.
   */
  public function testPreprocessMediaWithDifferentBundle(): void {
    $media = $this->createMock(MediaInterface::class);
    $media->method('bundle')->willReturn('video');

    $variables = [
      'attributes' => [],
      'elements' => ['#media' => $media],
    ];

    $this->hooks->preprocessMedia($variables);

    $this->assertSame(
      ['media-entity-wrapper', 'video'],
      $variables['attributes']['class']
    );
  }

  /**
   * Pre-existing classes should be preserved and appended to.
   */
  public function testPreprocessMediaAppendsToExistingClasses(): void {
    $media = $this->createMock(MediaInterface::class);
    $media->method('bundle')->willReturn('document');

    $variables = [
      'attributes' => ['class' => ['existing-class']],
      'elements' => ['#media' => $media],
    ];

    $this->hooks->preprocessMedia($variables);

    $this->assertSame(
      ['existing-class', 'media-entity-wrapper', 'document'],
      $variables['attributes']['class']
    );
  }

}
