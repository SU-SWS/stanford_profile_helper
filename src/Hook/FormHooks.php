<?php

declare(strict_types=1);

namespace Drupal\stanford_profile_helper\Hook;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\State\StateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;

/**
 * Form event subscriber.
 */
class FormHooks {

  use StringTranslationTrait;

  /**
   * Event subscriber constructor.
   *
   * @param \Drupal\Core\Session\AccountProxyInterface $currentUser
   *   Current user account.
   * @param \Drupal\Core\State\StateInterface $state
   *   Core state service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $configFactory
   *   Config factory service.
   */
  public function __construct(protected AccountProxyInterface $currentUser, protected StateInterface $state, protected ConfigFactoryInterface $configFactory) {}

  /**
   * Alter the field widget form.
   */
  #[Hook('field_widget_complete_form_alter')]
  public function fieldWidgetFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context): void {
    /** @var \Drupal\Core\Field\FieldItemList $items */
    $items = $context['items'];
    if ($items->getFieldDefinition()->getName() == 'su_site_nobots') {
      $field_widget_complete_form['widget']['value']['#default_value'] = (bool) $this->state->get('nobots');
    }

    // Change the default value label in the Spacer paragraph.
    if ($items->getFieldDefinition()->getName() == 'su_spacer_size') {
      $field_widget_complete_form['widget']['#options']['_none'] = 'Standard';
    }

    // Hide token help in the viewfield widget.
    if ($context['widget']->getPluginId() == 'viewfield_select') {
      $field_widget_complete_form['widget'][0]['view_options']['arguments']['#description'] = '';
      foreach (Element::children($field_widget_complete_form['widget']) as $delta) {
        unset($field_widget_complete_form['widget'][$delta]['view_options']['token_help']);
      }
    }
  }

  /**
   * Alter the taxonomy term form.
   */
  #[Hook('form_taxonomy_term_form_alter')]
  public function taxonomyTermFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    $form['name']['arg_helper'] = [
      '#type' => 'textfield',
      '#title' => $this->t('List Filtering Argument'),
      '#description' => $this->t('Use this string in the list paragraph to filter for content tagged with this term.'),
      '#default_value' => self::cleanString($form['name']['widget']['0']['value']['#default_value'] ?? ''),
      '#attributes' => ['disabled' => TRUE],
      '#prefix' => '<div id="arg-helper">',
      '#suffix' => '</div>',
    ];
    $form['name']['arg_helper_refresh'] = [
      '#type' => 'button',
      '#value' => $this->t('Refresh Argument'),
      '#ajax' => [
        'callback' => [self::class, 'argHelperAjaxCallback'],
        'wrapper' => 'arg-helper',
        'event' => 'focus',
      ],
    ];
  }

  /**
   * Ajax callback for the taxonomy term form.
   *
   * @param array $form
   *   Complete form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Ajax form state.
   *
   * @return array
   *   Altered arg helper form element.
   */
  public static function argHelperAjaxCallback(array &$form, FormStateInterface $form_state): array {
    $term_name = $form_state->getValue(['name', '0', 'value']);
    $form['name']['arg_helper']['#value'] = self::cleanString($term_name ?: '');
    return $form['name']['arg_helper'];
  }

  /**
   * Run the string through path auto alias cleaner.
   *
   * @param string $string
   *   String to clean.
   *
   * @return string
   *   Cleaned string.
   */
  protected static function cleanString(string $string): string {
    return \Drupal::service('pathauto.alias_cleaner')->cleanString($string);
  }

  /**
   * Modify the taxonomy overview form to hide vocabs the user doesn't need.
   */
  #[Hook('form_taxonomy_overview_vocabularies_alter')]
  public function taxonomyOverviewFormAlter(&$form, FormStateInterface $form_state): void {
    if ($this->currentUser->hasPermission('administer taxonomy')) {
      return;
    }

    foreach (Element::children($form['vocabularies']) as $vid) {
      unset($form['vocabularies'][$vid]['weight']);
      if (
        !$this->currentUser->hasPermission("create terms in $vid") &&
        !$this->currentUser->hasPermission("delete terms in $vid") &&
        !$this->currentUser->hasPermission("edit terms in $vid")
      ) {
        unset($form['vocabularies'][$vid]);
      }
    }
    unset($form['vocabularies']['#tabledrag']);
    unset($form['vocabularies']['#header']['weight'], $form['actions']);
  }

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_complete_options_select_form_alter')]
  public function fieldWidgetCompleteOptionsSelectFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context) {
    /** @var \Drupal\Core\Field\FieldItemListInterface $items */
    $items = $context['items'];
    $field_def = $items->getFieldDefinition();
    if ($field_def->getName() == 'layout_selection') {
      $field_widget_complete_form['widget']['#description'] = t('Choose a layout to display the page as a whole. Choose "- Default -" to keep the default layout setting.');
      $noneOption = $this->t('- Default -');

      // Special case for stanford_news content type.
      if ($field_def->getTargetBundle() == 'stanford_news') {
        $noneOption = $this->t('News');
        $field_widget_complete_form['widget']['#title'] = t('Variant');
        $field_widget_complete_form['widget']['#description'] = t('Select how this News item should be displayed, as a standard News article or a Spotlight feature.');
      }
      $field_widget_complete_form['widget']['#options']['_none'] = $noneOption;
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   */
  #[Hook('form_node_form_alter')]
  public function formNodeFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    unset($form['actions']['unlock'], $form['scheduler_settings']);
    $node = $form_state->getBuildInfo()['callback_object']->getEntity();
    $system_pages = $this->configFactory->get('system.site')->get('page');
    $access = !in_array('/node/' . $node->id(), $system_pages);

    $scheduler_increment = $this->state
      ->get('stanford_profile_helper.scheduler_increment', 60 * 60 * 4);
    $hours = (int) floor($scheduler_increment / 3600);
    $mins = (int) floor($scheduler_increment / 60 % 60);
    $scheduler_increment = $hours ? "$hours hour(s)" : "$mins minute(s)";
    $example_start = new \DateTime('today 8:00 AM');
    $example_end = clone $example_start;
    if ($hours > 0) {
      $example_end->modify("+$hours hours");
    }
    $example_end->modify("+$mins minutes");

    $help_text = [
      $this->t('Select a date and time* to publish this content in the future.'),
      $this->t('After scheduling the publish, it will automatically publish to your site on the scheduled date within @times of the selected time.', [
        '@times' => $scheduler_increment,
      ]),
      $this->t('For example, if you select @start as the publish time, the content will be published between @start and @end.', [
        '@start' => $example_start->format('H:i'),
        '@end' => $example_end->format('H:i'),
      ]),
      $this->t('<p><strong>*Note</strong>: You must select a time that is increments of @times, starting with 12AM.</p>', [
        '@times' => $scheduler_increment,
      ]),
    ];

    $form['scheduling'] = [
      '#type' => 'container',
      '#group' => 'revision_information',
      '#access' => (isset($form['unpublish_on']) || isset($form['unpublish_on'])) && !in_array('/node/' . $node->id(), $system_pages),
      'help' => ['#markup' => implode(' ', $help_text)],
      '#weight' => 999,
    ];

    if (isset($form['unpublish_on'])) {
      $form['unpublish_on']['#group'] = 'revision_information';
      $form['unpublish_on']['#weight'] = 55;
      $form['scheduling']['unpublish_on'] = $form['unpublish_on'];
    }

    if (isset($form['publish_on'])) {
      $form['publish_on']['#group'] = 'revision_information';
      $form['publish_on']['#weight'] = 50;
      $form['unpublish_on']['#access'] = $access;
      $status_element = &$form['status']['widget']['value'];
      $status_element['#states'] = [
        'disabled' => [':input[name="publish_on[0][value][time]"]' => ['filled' => TRUE]],
      ];
      $form['scheduling']['publish_on'] = $form['publish_on'];
    }
    unset($form['publish_on'], $form['unpublish_on']);
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id): void {
    if (strpos($form_id, 'views_form_') === 0) {
      // Remove the select all since it selects every node, not just the ones
      // from the active filters.
      // @link https://www.drupal.org/project/views_bulk_operations/issues/3055770#comment-13116724
      unset($form['header']['views_bulk_operations_bulk_form']['select_all']);

      // Sort the action menu options alphabetically.
      if (!empty($form['header']['views_bulk_operations_bulk_form']['action']['#options'])) {
        $actions_array = $form['header']['views_bulk_operations_bulk_form']['action']['#options'];
        uasort($actions_array, function ($a, $b) {
          return strcasecmp((string) $a, (string) $b);
        });
        $form['header']['views_bulk_operations_bulk_form']['action']['#options'] = $actions_array;
      }
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_views_bulk_operations_configure_action_alter')]
  public function formViewsBulkOperationsConfigureActionAlter(&$form, FormStateInterface $form_state, $form_id): void {
    if (!empty($form['node']['stanford_event']['su_event_date_time']['widget'])) {
      $form['node']['stanford_event']['su_event_date_time']['widget'][0]['time_wrapper']['value']['#required'] = FALSE;
      $form['node']['stanford_event']['su_event_date_time']['widget'][0]['time_wrapper']['end_value']['#required'] = FALSE;
    }
  }

  /**
   * Implements hook_form_FORM_ID_alter().
   */
  #[Hook('form_taxonomy_overview_terms_alter')]
  public function formTaxonomyOverviewTermsAlter(&$form, FormStateInterface $form_state): void {
    if ($form_state->get('taxonomy')['vocabulary']->id() == 'stanford_publication_topics') {
      $url = Url::fromUri('https://userguide.sites.stanford.edu/tour/publications#publications-list-page');
      $link = Link::fromTextAndUrl($this->t('default Publications List Page'), $url)
        ->toString();
      $form['citation_format']['#title'] = $this->t('Citation Style');
      $form['citation_format']['#description'] = $this->t('Select citation format for the %link. *<strong>CAUTION</strong>: The default Publication list page uses Chicago as the citation style. If you select a different citation format here, you should also update the citation format on the default Publications List Page that uses a "filter by topics" menu.', ['%link' => $link]);
    }
  }

  /**
   * Implements hook_form_BASE_FORM_ID_alter().
   *
   * Tweaks the taxonomy term add/edit form to only allow a single parent
   * term when the vocabulary is not configured as a flat taxonomy.
   */
  #[Hook('form_taxonomy_term_form_alter')]
  public function taxonomyTermParentFormAlter(&$form, FormStateInterface $form_state, $form_id): void {
    /** @var \Drupal\taxonomy\VocabularyInterface $vocabulary */
    $vocabulary = $form_state->get(['taxonomy', 'vocabulary']);
    $flat_taxonomy = $vocabulary->getThirdPartySetting('flat_taxonomy', 'flat');

    // Tweak the taxonomy term add/edit form.
    if (!empty($form['relations']['parent']) && !$flat_taxonomy) {
      $form['relations']['#open'] = TRUE;
      $form['relations']['parent']['#multiple'] = FALSE;
      $form['relations']['parent']['#title'] = $this->t('Parent term');
      $form['relations']['parent']['#description'] = $this->t('Select the appropriate parent item for this term.');
      $form['relations']['parent']['#element_validate'][] = [self::class, 'termFormValidate'];
    }
  }

  /**
   * Tweak the taxonomy term parent form value after submitting.
   *
   * Because we are changing the form to not allow multiple parents, the form
   * value needs to be changed into an array so the TermForm can still manage
   * it correctly.
   *
   * @param array $element
   *   Form element.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Current form state object.
   * @param array $form
   *   Complete form.
   *
   * @see self::taxonomyTermParentFormAlter()
   */
  public static function termFormValidate(array $element, FormStateInterface $form_state, array $form): void {
    $form_state->setValueForElement($element, [$element['#value']]);
  }

  /**
   * Implements hook_field_widget_complete_WIDGET_TYPE_form_alter().
   */
  #[Hook('field_widget_complete_datetime_timestamp_no_default_form_alter')]
  public function fieldWidgetCompleteDatetimeTimestampNoDefaultFormAlter(&$field_widget_complete_form, FormStateInterface $form_state, $context): void {
    // Set the date increment for scheduler settings.
    $field_widget_complete_form['widget'][0]['value']['#date_increment'] = $this->state->get('stanford_profile_helper.scheduler_increment', 60 * 60 * 4);
  }

}
