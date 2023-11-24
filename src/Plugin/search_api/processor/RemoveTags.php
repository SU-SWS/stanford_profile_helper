<?php

namespace Drupal\stanford_profile_helper\Plugin\search_api\processor;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api\Processor\FieldsProcessorPluginBase;

/**
 * Search API processor to remove only the desired html tags.
 *
 * @SearchApiProcessor(
 *    id = "remove_tags",
 *    label = @Translation("Remove Specific HTML Tags"),
 *    description = @Translation("Similiar to 'strip_tags', but choose only which tags to strip from fields."),
 *    stages = {
 *      "preprocess_index" = 0,
 *    }
 *  )
 */
class RemoveTags extends FieldsProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $config = parent::defaultConfiguration();
    $config['all_fields'] = TRUE;
    $config += [
      'tags' => [
        'div',
        'span',
      ],
    ];
    return $config;
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);
    $form['tags'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Tags to Remove'),
      '#description' => $this->t('One tag per line'),
      '#default_value' => implode("\n", $this->configuration['tags']),
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    parent::validateConfigurationForm($form, $form_state);
    $tags = explode("\n", $form_state->getValue('tags'));
    foreach ($tags as &$tag) {
      $tag = trim($tag);
      if (!preg_match('/^(\w+)$/', $tag)) {
        $form_state->setError($form['tags'], $this->t('Invalid tag.'));
        return;
      }
    }
    $form_state->setValue('tags', array_filter($tags));
  }

  /**
   * {@inheritdoc}
   */
  protected function processFieldValue(&$value, $type) {
    $text = str_replace('><', '> <', $value);

    // Remove all html comments.
    $text = trim(preg_replace('/<!--(.*)-->/Uis', '', $text));

    // Find all the html tags.
    preg_match_all('/<(\w+)/', $text, $tags_matched);
    $text_tags = $tags_matched[1] ?? [];

    // Find the difference of the allowed tags and the tags in the content.
    $keep_tags = array_diff($text_tags, $this->configuration['tags']);

    // Remove the tags and clean up whitespace.
    $value = preg_replace('/\s\s+/', "\n", trim(strip_tags($text, $keep_tags)));
  }

}
