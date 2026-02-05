<?php

declare(strict_types=1);

namespace Drupal\Tests\stanford_profile_helper\Unit\Form;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\layout_library\Entity\Layout;
use Drupal\stanford_profile_helper\Form\LayoutLibraryIconForm;
use Drupal\stanford_profile_helper\LayoutLibraryIconInterface;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the LayoutLibraryIconForm.
 */
#[Group('stanford_profile_helper')]
#[CoversClass(LayoutLibraryIconForm::class)]
class LayoutLibraryIconFormTest extends UnitTestCase {

  protected $form;
  protected $entity;
  protected $entityTypeManager;
  protected $messenger;
  protected $translation;

  protected function setUp(): void {
    parent::setUp();

    $this->translation = $this->createMock(TranslationInterface::class);
    $this->translation->method('translate')
      ->willReturnCallback(function ($string) {
        return new TranslatableMarkup($string, [], [], $this->translation);
      });

    $this->entity = $this->createMock(Layout::class);
    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->messenger = $this->createMock(MessengerInterface::class);

    $this->form = new LayoutLibraryIconForm();
    $this->form->setStringTranslation($this->translation);
    $this->form->setEntityTypeManager($this->entityTypeManager);
    $this->form->setEntity($this->entity);
    $this->form->setMessenger($this->messenger);
  }

  public function testGetFormId() {
    $form_id = $this->form->getFormId();
    $this->assertEquals('layout_library_icon', $form_id);
  }

  public function testBuildFormWithoutExistingIcon() {
    // Entity has no icon set.
    $this->entity->method('getThirdPartySetting')
      ->with('stanford_profile_helper', 'icon', [])
      ->willReturn([]);

    $form_state = $this->createMock(FormStateInterface::class);
    $form = [];

    $result = $this->form->buildForm($form, $form_state);

    // Assertions.
    $this->assertArrayHasKey('icon', $result);
    $this->assertEquals('managed_file', $result['icon']['#type']);
    $this->assertEquals(LayoutLibraryIconInterface::IMAGE_DIRECTORY, $result['icon']['#upload_location']);
    $this->assertArrayHasKey('FileExtension', $result['icon']['#upload_validators']);
    $this->assertEquals(['extensions' => 'png jpg svg'], $result['icon']['#upload_validators']['FileExtension']);
    $this->assertArrayNotHasKey('#default_value', $result['icon']);
    $this->assertArrayHasKey('actions', $result);
    $this->assertEquals('actions', $result['actions']['#type']);
    $this->assertEquals('submit', $result['actions']['submit']['#type']);
  }

  public function testBuildFormWithExistingIcon() {
    // Create mock file.
    $file = $this->createMock(FileInterface::class);
    $file->method('id')->willReturn(42);
    $file->method('uuid')->willReturn('test-uuid-123');

    // Entity has icon with UUID.
    $this->entity->method('getThirdPartySetting')
      ->with('stanford_profile_helper', 'icon', [])
      ->willReturn(['uuid' => 'test-uuid-123']);

    // Mock file storage.
    $file_storage = $this->createMock(EntityStorageInterface::class);
    $file_storage->method('loadByProperties')
      ->with(['uuid' => 'test-uuid-123'])
      ->willReturn([$file]);

    $this->entityTypeManager->method('getStorage')
      ->with('file')
      ->willReturn($file_storage);

    $form_state = $this->createMock(FormStateInterface::class);
    $form = [];

    $result = $this->form->buildForm($form, $form_state);

    // Assertions - should have default value set.
    $this->assertArrayHasKey('icon', $result);
    $this->assertArrayHasKey('#default_value', $result['icon']);
    $this->assertEquals(['target_id' => 42], $result['icon']['#default_value']);
  }

  public function testBuildFormWithInvalidIconUuid() {
    // Entity has icon with UUID but file doesn't exist.
    $this->entity->method('getThirdPartySetting')
      ->with('stanford_profile_helper', 'icon', [])
      ->willReturn(['uuid' => 'invalid-uuid']);

    // Mock file storage - returns empty array.
    $file_storage = $this->createMock(EntityStorageInterface::class);
    $file_storage->method('loadByProperties')
      ->with(['uuid' => 'invalid-uuid'])
      ->willReturn([]);

    $this->entityTypeManager->method('getStorage')
      ->with('file')
      ->willReturn($file_storage);

    $form_state = $this->createMock(FormStateInterface::class);
    $form = [];

    $result = $this->form->buildForm($form, $form_state);

    // Assertions - should not have default value.
    $this->assertArrayHasKey('icon', $result);
    $this->assertArrayNotHasKey('#default_value', $result['icon']);
  }

  public function testSubmitFormWithoutIcon() {
    // Mock form state with no icon value.
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')
      ->with(['icon', '0'])
      ->willReturn(NULL);

    // Mock URL.
    $url = $this->createMock(Url::class);
    $this->entity->method('toUrl')
      ->with('collection')
      ->willReturn($url);

    $form_state->expects($this->once())
      ->method('setRedirectUrl')
      ->with($url);

    // Expect the third party setting to be unset.
    $this->entity->expects($this->once())
      ->method('unsetThirdPartySetting')
      ->with('stanford_profile_helper', 'icon');

    $this->entity->expects($this->never())->method('setThirdPartySetting');
    $this->entity->expects($this->once())->method('save');
    $this->messenger->expects($this->once())->method('addStatus');

    $form = [];
    $this->form->submitForm($form, $form_state);
  }

  public function testSubmitFormWithInvalidFileId() {
    // Mock file storage to return null.
    $file_storage = $this->createMock(EntityStorageInterface::class);
    $file_storage->method('load')
      ->with(999)
      ->willReturn(NULL);

    $this->entityTypeManager->method('getStorage')
      ->with('file')
      ->willReturn($file_storage);

    // Mock form state with invalid file ID.
    $form_state = $this->createMock(FormStateInterface::class);
    $form_state->method('getValue')
      ->with(['icon', '0'])
      ->willReturn(999);

    // Mock URL.
    $url = $this->createMock(Url::class);
    $this->entity->method('toUrl')
      ->with('collection')
      ->willReturn($url);

    // Expect the third party setting to be unset since file doesn't exist.
    $this->entity->expects($this->once())
      ->method('unsetThirdPartySetting')
      ->with('stanford_profile_helper', 'icon');

    $this->entity->expects($this->never())->method('setThirdPartySetting');
    $this->entity->expects($this->once())->method('save');

    $form = [];
    $this->form->submitForm($form, $form_state);
  }

}
