<?php

namespace Drupal\Tests\stanford_policy\Unit\Form;

use Drupal\Core\Form\FormState;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\stanford_policy\Form\SuPolicyLogSettingsForm;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\Group;

/**
 * Test the policy log settings form.
 */
#[Group('stanford_policy')]
class SuPolicyLogSettingsFormTest extends UnitTestCase {

  /**
   * The settings form builds and reports on submit.
   */
  public function testForm(): void {
    $messenger = $this->createMock(MessengerInterface::class);
    $messenger->expects($this->once())
      ->method('addStatus')
      ->with($this->callback(fn($message) => (string) $message === 'The configuration has been updated.'));

    $form_object = new SuPolicyLogSettingsForm();
    $form_object->setStringTranslation($this->getStringTranslationStub());
    $form_object->setMessenger($messenger);

    $this->assertEquals('su_policy_log_settings', $form_object->getFormId());

    $form_state = new FormState();
    $form = $form_object->buildForm([], $form_state);
    $this->assertEquals('Settings form for a policy log entity type.', (string) $form['settings']['#markup']);
    $this->assertEquals('actions', $form['actions']['#type']);
    $this->assertEquals('submit', $form['actions']['submit']['#type']);
    $this->assertEquals('Save', (string) $form['actions']['submit']['#value']);

    $form_object->submitForm($form, $form_state);
  }

}
