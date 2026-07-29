<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_image_styles\Unit\Hook;

use Drupal\Core\Extension\ModuleExtensionList;
use Drupal\image\ImageStyleInterface;
use Drupal\stanford_image_styles\Hook\StanfordImageStylesHooks;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Unit tests for StanfordImageStylesHooks.
 */
#[Group('stanford_image_styles')]
#[CoversClass(StanfordImageStylesHooks::class)]
class StanfordImageStylesHooksTest extends UnitTestCase {

  /**
   * Mocked module extension list service.
   *
   * @var \Drupal\Core\Extension\ModuleExtensionList&\PHPUnit\Framework\MockObject\MockObject
   */
  protected ModuleExtensionList $moduleExtensionList;

  /**
   * The hooks class under test.
   *
   * @var \Drupal\stanford_image_styles\Hook\StanfordImageStylesHooks
   */
  protected StanfordImageStylesHooks $hooks;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->moduleExtensionList = $this->createMock(ModuleExtensionList::class);
    $this->hooks = new StanfordImageStylesHooks($this->moduleExtensionList);
  }

  /**
   * Image styles other than stanford_circle are left untouched.
   */
  public function testPresaveWrongId(): void {
    $image_style = $this->createMock(ImageStyleInterface::class);
    $image_style->method('id')->willReturn('some_other_style');
    $image_style->expects($this->never())->method('isNew');
    $image_style->expects($this->never())->method('get');
    $image_style->expects($this->never())->method('set');

    $this->hooks->imageStylePresave($image_style);
  }

  /**
   * An existing (not new) stanford_circle style is left untouched.
   */
  public function testPresaveCircleNotNew(): void {
    $image_style = $this->createMock(ImageStyleInterface::class);
    $image_style->method('id')->willReturn('stanford_circle');
    $image_style->method('isNew')->willReturn(FALSE);
    $image_style->expects($this->never())->method('get');
    $image_style->expects($this->never())->method('set');

    $this->hooks->imageStylePresave($image_style);
  }

  /**
   * A new stanford_circle style without a mask effect still gets re-set,
   * unchanged, and the module path is never looked up.
   */
  public function testPresaveNewCircleWithoutMaskEffect(): void {
    $effects = [
      'other_effect' => ['id' => 'image_scale', 'data' => []],
    ];

    $image_style = $this->createMock(ImageStyleInterface::class);
    $image_style->method('id')->willReturn('stanford_circle');
    $image_style->method('isNew')->willReturn(TRUE);
    $image_style->method('get')->with('effects')->willReturn($effects);
    $this->moduleExtensionList->expects($this->never())->method('getPath');
    $image_style->expects($this->once())
      ->method('set')
      ->with('effects', $effects);

    $this->hooks->imageStylePresave($image_style);
  }

  /**
   * A new stanford_circle style with a mask effect gets the mask image path
   * set from the module path.
   */
  public function testPresaveNewCircleWithMaskEffect(): void {
    $effects = [
      'mask_effect' => ['id' => 'image_effects_mask', 'data' => []],
      'other_effect' => ['id' => 'image_scale', 'data' => []],
    ];

    $image_style = $this->createMock(ImageStyleInterface::class);
    $image_style->method('id')->willReturn('stanford_circle');
    $image_style->method('isNew')->willReturn(TRUE);
    $image_style->method('get')->with('effects')->willReturn($effects);

    $this->moduleExtensionList->method('getPath')
      ->with('stanford_image_styles')
      ->willReturn('modules/custom/stanford_image_styles');

    $captured = NULL;
    $image_style->expects($this->once())
      ->method('set')
      ->with(
        'effects',
        $this->callback(function ($value) use (&$captured) {
          $captured = $value;
          return TRUE;
        })
      );

    $this->hooks->imageStylePresave($image_style);

    $this->assertSame(
      'modules/custom/stanford_image_styles/img/mask-image.png',
      $captured['mask_effect']['data']['mask_image']
    );
    $this->assertSame('image_scale', $captured['other_effect']['id']);
  }

}
