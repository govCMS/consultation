<?php

namespace Drupal\consultation\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\filter\FilterPluginBase;

/**
 * Open/Closed/Upcoming state filter for consultations.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("consultation_state")
 */
class ConsultationState extends FilterPluginBase {

  /**
   * Operators.
   */
  public function operators() {
    $operators = [
      'is' => [
        'title' => $this->t('Is state'),
        'method' => 'opStateIs',
        'short' => $this->t('Is'),
        'values' => 1,
      ],
    ];
  }

  /**
   * Add a type selector to the value form
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $form['value'] = [
      '#tree' => TRUE,
      'state' => [
        '#type' => 'select',
        '#title' => $this->t('Status'),
        '#options' => [
          'all' => $this->t('All'),
          'open' => $this->t('Open'),
          'closed' => $this->t('Closed'),
          'upcoming' => $this->t('Upcoming'),
        ],
        '#default_value' => !empty($this->value['state']) ? $this->value['state'] : 'all',
      ]
    ];
  }

  /**
   * Applying query filter.
   */
  public function query() {
    $this->ensureMyTable();
    $start_field_name = "$this->tableAlias.$this->realField";
    $end_field_name = substr($start_field_name, 0, -6) . '_end_value';

    // Prepare sql clauses for each field.
    $date_start = $this->query->getDateFormat($this->query->getDateField($start_field_name, TRUE, FALSE), 'Y-m-d H:i:s', FALSE);
    $date_end = $this->query->getDateFormat($this->query->getDateField($end_field_name, TRUE, FALSE), 'Y-m-d H:i:s', FALSE);
    $date_now = $this->query->getDateFormat('FROM_UNIXTIME(***CURRENT_TIME***)', 'Y-m-d H:i:s', FALSE);

    switch ($this->value['state']) {
      case 'open':
        $this->query->addWhereExpression($this->options['group'], "($date_now BETWEEN $date_start AND $date_end) OR (node__field_cons_late_subs.field_cons_late_subs_value = 1)");
        break;

      case 'closed':
        $this->query->addWhereExpression($this->options['group'], "($date_now > $date_end) AND (node__field_cons_late_subs.field_cons_late_subs_value <> 1)");
        break;

      case 'upcoming':
        $this->query->addWhereExpression($this->options['group'], "($date_now < $date_start) AND (node__field_cons_late_subs.field_cons_late_subs_value <> 1)");
        break;
    }
  }

  /**
   * Admin summary.
   */
  public function adminSummary() {

    if ($this->isAGroup()) {
      return $this->t('grouped');
    }
    if (!empty($this->options['exposed'])) {
      return $this->t('exposed') . ', ' . $this->t('default state') . ': ' . $this->value['state'];
    }
    else {
      return $this->t('state') . ': ' . $this->value['state'];
    }
  }

}
