<?php

namespace Drupal\computed_field\Plugin\Field\FieldType;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\StringItemBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Plugin implementation of the 'computed_string' field type.
 *
 * @FieldType(
 *   id = "computed_string",
 *   label = @Translation("Computed (text)"),
 *   description = @Translation("This field defines a text field whose value is computed by PHP-Code"),
 *   category = @Translation("Computed"),
 *   default_widget = "computed_string_widget",
 *   default_formatter = "computed_string"
 * )
 */
class ComputedStringItem extends StringItemBase {

  /**
   * {@inheritdoc}
   */
  public static function defaultFieldSettings() {
    return [
      'code' => '$value = \'\';',
    ] + parent::defaultFieldSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function defaultStorageSettings() {
    return [
      'max_length' => 255,
      'is_ascii' => FALSE,
    ] + parent::defaultStorageSettings();
  }

  /**
   * {@inheritdoc}
   */
  public static function schema(FieldStorageDefinitionInterface $field_definition) {
    $settings = $field_definition->getSettings();
    $schema = [
      'columns' => [
        'value' => [
          'type' => $settings['is_ascii'] === TRUE ? 'varchar_ascii' : 'varchar',
          'length' => (int) $settings['max_length'],
          'binary' => $settings['case_sensitive'],
        ],
      ],
    ];

    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public static function generateSampleValue(FieldDefinitionInterface $field_definition) {
    /**
     * @Todo: add useful code
     */
    $values['value'] = '';
    return $values;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $settings = $this->getSettings();

    $element['code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code (PHP) to compute the <em>text</em> value'),
      '#default_value' => $settings['code'],
      '#required' => TRUE,
      '#description' =>
        t('The variables available to your code include:
<ul>
<li><code>$value</code>: the resulting value (to be set in this code),</li>
<li><code>$fields</code>: the list of fields available in this entity,</li>
<li><code>$entity</code>: the entity the field belongs to,</li>
<li><code>$entity_manager</code>: the entity manager service (<em>deprecated!</em>),</li>
<li><code>$entity_type_manager</code>: the entity type manager,</li>
<li><code>$delta</code>: current index of the field in case of multi-value computed fields (counting from 0).</li>
</ul>')
        . '<p>'
        . t('The value will be truncated to @max_length characters.', ['@max_length' => $settings['max_length']])
        . '</p>'
        . '<p>'
        . t('Here\'s a simple example using the <code>$entity</code>-array which sets the computed field\'s value to the concatenation of fields (<code>field_a</code> and <code>field_b</code>) in an entity:')
        . '<ul><li><code>$value = $entity->field_a->value . $entity->field_b->value;</code></li></ul>'
        . '<p>'
        . t('An alternative example using the <code>$fields</code>-array:')
        . '<ul><li><code>$value = $fields[\'field_a\'][0][\'value\'] . $fields[\'field_b\'][0][\'value\'];</code></li></ul>'
        . '</p>'
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function storageSettingsForm(array &$form, FormStateInterface $form_state, $has_data) {
    $settings = $this->getSettings();
    $element = [];

    $element['max_length'] = [
      '#type' => 'number',
      '#title' => t('Maximum length'),
      '#default_value' => $settings['max_length'],
      '#required' => TRUE,
      '#description' => t('The maximum length of the field in characters.'),
      '#min' => 1,
      '#disabled' => $has_data,
    ];

    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function isEmpty() {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function preSave() {
    $value = $this->executeCode();
    $this->setValue($value);
  }

  /**
   * This function does the computation of the field value.
   * @return int
   */
  public function executeCode() {
    $settings = $this->getSettings();
    $code = $settings['code'];
    $entity_manager = \Drupal::EntityManager();
    $entity_type_manager = \Drupal::EntityTypeManager();
    $entity = $this->getEntity();
    $fields = $entity->toArray();
    $delta = $this->name; // indeed!
    $value = NULL;

    eval($code);
    return mb_substr($value, 0, $settings['max_length'] - 1);
  }

}
