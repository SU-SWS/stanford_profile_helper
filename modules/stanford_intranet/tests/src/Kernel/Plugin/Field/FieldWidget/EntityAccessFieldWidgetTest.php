<?php

namespace Drupal\Tests\stanford_intranet\Kernel\Plugin\Field\FieldWidget;

use Drupal\Core\Entity\Entity\EntityFormDisplay;
use Drupal\Core\Form\FormState;
use Drupal\node\Entity\Node;
use Drupal\stanford_intranet\Plugin\Field\FieldType\EntityAccessFieldType;
use Drupal\Tests\stanford_intranet\Kernel\IntranetKernelTestBase;
use Drupal\user\Entity\Role;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\Attributes\Group;

/**
 * Kernel tests for the intranet entity access field widget.
 */
#[RunTestsInSeparateProcesses]
#[Group('stanford_intranet')]
class EntityAccessFieldWidgetTest extends IntranetKernelTestBase {

  /**
   * Page node used in the tests.
   *
   * @var \Drupal\node\NodeInterface
   */
  protected $node;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = EntityFormDisplay::create([
      'targetEntityType' => 'node',
      'bundle' => 'page',
      'mode' => 'default',
      'status' => TRUE,
    ]);
    $form_display->setComponent('stanford_intranet__access', [
      'type' => 'entity_access',
      'settings' => [],
    ]);
    $form_display->save();

    /** @var \Drupal\user\UserInterface $user */
    $user = User::create(['name' => $this->randomMachineName()]);
    $user->save();
    /** @var \Drupal\node\NodeInterface $node */
    $this->node = Node::create(['title' => 'Foo Bar', 'type' => 'page']);
    $this->node->setOwner($user);
    $this->node->save();
  }

  /**
   * Saving the node form should save the field widget.
   */
  public function testFieldWidgetSubmit() {
    \Drupal::state()->set('stanford_intranet', TRUE);
    Role::create(['id' => 'student', 'label' => 'Student'])->save();
    /** @var \Drupal\Core\Entity\EntityFormBuilderInterface $entity_form_builder */
    $entity_form_builder = \Drupal::service('entity.form_builder');
    $form = $entity_form_builder->getForm($this->node);

    /** @var \Drupal\node\Form\NodeForm $form_object */
    $form_object = \Drupal::entityTypeManager()->getFormObject('node', 'edit');
    $form_object->setEntity($this->node);

    $form_state = new FormState();
    $form_state->setBuildInfo(['callback_object' => $form_object]);
    $form_state->set('form_display', EntityFormDisplay::load('node.page.default'));

    $form_state->setValue(EntityAccessFieldType::FIELD_NAME, ['student']);
    $form_object->validateForm($form, $form_state);
    $form_object->submitForm($form, $form_state);
    $form_object->save($form, $form_state);

    \Drupal::entityTypeManager()->getStorage('node')->resetCache();
    $new_field_value = Node::load($this->node->id())
      ->get(EntityAccessFieldType::FIELD_NAME)
      ->getValue();
    $this->assertEquals('student', $new_field_value[0]['role']);
    $this->assertEquals('view', $new_field_value[0]['access'][0]);
  }

}
