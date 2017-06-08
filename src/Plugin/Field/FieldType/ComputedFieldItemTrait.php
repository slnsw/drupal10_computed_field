<?php

namespace Drupal\computed_field\Plugin\Field\FieldType;

use Drupal\Core\Form\FormStateInterface;

/**
 * Common methods for Computed Field FieldType plugins.
 *
 * The FieldType plugins in this module descend from either FieldItemBase
 * (numbers via ComputedFieldItemBase) or StringItemBase (strings via
 * ComputedStringItemBase). As they have no common ancestry outside of Core,
 * it's necessary to introduce this trait to prevent code duplication across
 * hierarchies.
 */
trait ComputedFieldItemTrait {

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
   * Performs the field value computation.
   */
  public function executeCode() {
    $code = $this->getSettings()['code'];

    // Make these variables available to users for use in their code.
    $entity_type_manager = \Drupal::EntityTypeManager();
    $entity = $this->getEntity();
    $fields = $entity->toArray();
    $delta = $this->name;

    $value = NULL;
    eval($code);
    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function fieldSettingsForm(array $form, FormStateInterface $form_state) {
    $element = [];
    $settings = $this->getSettings();

    $element['code'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Code (PHP) to compute the value'),
      '#default_value' => $settings['code'],
      '#required' => TRUE,
      '#description' => t('The variables available to your code include:
<ul>
<li><code>$value</code>: the resulting value (to be set in this code),</li>
<li><code>$fields</code>: the list of fields available in this entity,</li>
<li><code>$entity</code>: the entity the field belongs to,</li>
<li><code>$entity_type_manager</code>: the entity type manager,</li>
<li><code>$delta</code>: current index of the field in case of multi-value computed fields (counting from 0).</li>
</ul>
      '),
    ];

    return $element;
  }

}
