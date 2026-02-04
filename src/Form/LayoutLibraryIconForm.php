<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\stanford_profile_helper\LayoutLibraryIconInterface;

/**
 * Provides a Stanford Profile Helper form.
 */
final class LayoutLibraryIconForm extends EntityForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'layout_library_icon';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['icon'] = [
      '#title' => $this->t('Layout icon'),
      '#type' => 'managed_file',
      '#upload_location' => LayoutLibraryIconInterface::IMAGE_DIRECTORY,
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'png jpg svg'],
      ],
    ];

    if ($file = $this->getIconFile()) {
      $form['icon']['#default_value'] = ['target_id' => $file->id()];
    }

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Save'),
      ],
    ];

    return $form;
  }

  protected function getIconFile() {
    $icon = $this->entity->getThirdPartySetting('stanford_profile_helper', 'icon', []);
    if (isset($icon['uuid']) && $icon['uuid']) {
      $files = $this->entityTypeManager->getStorage('file')
        ->loadByProperties(['uuid' => $icon['uuid']]);
      if ($files) {
        return reset($files);
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    /** @var \Drupal\layout_library\Entity\Layout $layout */
    $layout = $this->entity;

    $this->messenger()->addStatus($this->t('The message has been sent.'));
    $form_state->setRedirectUrl($layout->toUrl('collection'));

    $icon_file = $form_state->getValue(['icon', '0']);
    // Set the icon file UUID and default value to the paragraph configuration.
    if (
      !empty($icon_file) &&
      $file = $this->entityTypeManager->getStorage('file')->load($icon_file)
    ) {
      $layout->setThirdPartySetting('stanford_profile_helper', 'icon', [
        'uuid' => $file->uuid(),
        'data' => 'data:' . $file->getMimeType() . ';base64,' . base64_encode(file_get_contents($file->getFileUri())),
      ]);
    }
    else {
      $layout->unsetThirdPartySetting('stanford_profile_helper', 'icon');
    }
    $layout->save();
  }

}
