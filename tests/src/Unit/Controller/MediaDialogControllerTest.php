<?php

namespace Drupal\Tests\stanford_profile_helper\Unit\Controller;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\EntityViewBuilderInterface;
use Drupal\media\MediaInterface;
use Drupal\stanford_profile_helper\Controller\MediaDialogController;
use Drupal\Tests\UnitTestCase;

/**
 * Test the MediaDialogController.
 */
class MediaDialogControllerTest extends UnitTestCase {

  /**
   * Media dialog controller.
   *
   * @var \Drupal\stanford_profile_helper\Controller\MediaDialogController
   */
  protected $controller;

  /**
   * Mock entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * Mock view builder.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $viewBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    \Drupal::setContainer($container);

    $this->viewBuilder = $this->createMock(EntityViewBuilderInterface::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityTypeManager->method('getViewBuilder')
      ->with('media')
      ->willReturn($this->viewBuilder);

    $this->controller = new MediaDialogController();
    $reflection = new \ReflectionClass($this->controller);
    $property = $reflection->getProperty('entityTypeManager');
    $property->setAccessible(TRUE);
    $property->setValue($this->controller, $this->entityTypeManager);
  }

  /**
   * Test the title method returns the media label.
   */
  public function testTitle() {
    $media = $this->createMock(MediaInterface::class);
    $media->method('label')->willReturn('Test Media Title');

    $result = $this->controller->title($media);

    $this->assertEquals('Test Media Title', $result);
  }

  /**
   * Test the mediaDialog method returns correct render array.
   */
  public function testMediaDialog() {
    $media = $this->createMock(MediaInterface::class);

    $expected_build = [
      '#theme' => 'media',
      '#media' => $media,
    ];

    $this->viewBuilder->method('view')
      ->with($media)
      ->willReturn($expected_build);

    $result = $this->controller->mediaDialog($media);

    $this->assertArrayHasKey('#attached', $result);
    $this->assertArrayHasKey('html_head', $result['#attached']);
    $this->assertIsArray($result['#attached']['html_head']);
    $this->assertCount(1, $result['#attached']['html_head']);

    $meta_tag = $result['#attached']['html_head'][0];
    $this->assertEquals('stanford_profile_helper', $meta_tag[1]);
    $this->assertArrayHasKey(0, $meta_tag);
    $this->assertEquals('meta', $meta_tag[0]['#tag']);
    $this->assertEquals('robots', $meta_tag[0]['#attributes']['name']);
    $this->assertEquals('noindex, nofollow', $meta_tag[0]['#attributes']['content']);
  }

}
