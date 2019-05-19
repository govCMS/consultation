<?php

namespace Drupal\consultation;

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

/**
 * Provides logic functions for consultation node type.
 *
 * This class in not complex, it just takes a node object and then
 * some methods to calculate values, mostly for theming.
 */
class ConsultationNodeHelper {

  /**
   * The consultation node.
   *
   * @var \Drupal\node\Entity\Node
   */
  private $node;

  /**
   * The consultation start date.
   *
   * @var \Drupal\Component\Datetime\DateTimePlus
   */
  private $startDate;

  /**
   * The consultation end date.
   *
   * @var \Drupal\Component\Datetime\DateTimePlus
   */
  private $endDate;

  // Twisted logic just trying to match element names, carry over from D7 webform element names.
  const SUB_DISPLAY_APPROVAL = 'display';
  const SUB_DISPLAY_APPROVAL_NOT = 'hide';
  const SUB_PRIVATE_SUBMISSION = 'private';
  const SUB_PRIVATE_SUBMISSION_NOT = 'public';
  const SUB_REMAIN_ANONYMOUS = 'anonymous';
  const SUB_REMAIN_ANONYMOUS_NOT = 'display';

  /**
   * ConsultationNodeHelper constructor.
   */
  public function __construct(Node $node) {
    if ('consultation' != $node->bundle()) {
      throw new \Exception('This class must be initialized with a consultation node.');
    }

    $this->node = $node;
    $this->startDate = $this->node->field_cons_date->start_date;
    $this->endDate = $this->node->field_cons_date->end_date;
  }

  /**
   * Get the consulation start date.
   *
   * @param bool $format
   *   Optionally date format. Returns the whole DateTimePlus object if missing.
   *
   * @return \Drupal\Component\Datetime\DateTimePlus|string
   *   Start date as object or formatted.
   */
  public function getDateStart($format = FALSE) {
    if ($format) {
      return $this->startDate->format($format);
    }
    else {
      return $this->startDate;
    }
  }

  /**
   * Get the consulation end date.
   *
   * @param bool $format
   *   Optionally date format. Returns the whole DateTimePlus object if missing.
   *
   * @return \Drupal\Component\Datetime\DateTimePlus|string
   *   End date as object or formatted.
   */
  public function getDateEnd($format = FALSE) {
    if ($format) {
      return $this->endDate->format($format);
    }
    else {
      return $this->endDate;
    }
  }

  /**
   * Get the total number of days for the consultation.
   *
   * @return int
   *   The number of days.
   */
  public function getDaysTotal() {
    return ($this->getDateEnd('U') - $this->getDateStart('U')) / 86400;
  }

  /**
   * Get the total number of days remaining for the consultation.
   *
   * @return int
   *   The number of days or 0 if it's ended.
   */
  public function getDaysRemaining() {
    if ($this->isNotStarted()) {
      return $this->getDaysTotal();
    }
    elseif ($this->isFinished()) {
      return 0;
    }
    else {
      return (time() - $this->getDateStart('U')) / 86400;
    }
  }

  /**
   * Return the percentage complete of the consultation.
   *
   * @return int
   *   Percentage as a number between 0 and 100.
   */
  public function getPercentageComplete() {
    if ($this->isNotStarted()) {
      return 0;
    }
    elseif ($this->isFinished()) {
      return 100;
    }
    else {
      $seconds = $this->getDateEnd('U') - $this->getDateStart('U');
      $seconds_remaining = $this->getDateEnd('U') - time();
      $percentage = $seconds_remaining / $seconds * 100;
      $percentage = max(0, min(100, $percentage));
      return round($percentage);
    }
  }

  // @phpcs:ignore
  public function getLateSubmissionUrl() {}

  public function getWebformId() {
    if (!$this->node->field_cons_webform->isEmpty()) {
      return $this->node->field_cons_webform->first()->entity->id();
    }
  }

  public function getPublicSubmissionsDisplay() {
    if (FALSE || $this->isSubmissionsNowPublic()) {
      return FALSE;
    }

    $webform_id = $this->getWebformId();
    $output = [];

    // @todo inject t.
    $header_table = [
      'name' => 'Submitted by',
      'submission' => 'Submission',
    ];

    $rows = [];
    $query = \Drupal::entityQuery('webform_submission')
      ->condition('webform_id', $webform_id);
    $result = $query->execute();

    $storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $submissions = $storage->loadMultiple($result);
    foreach ($submissions as $submission) {
      $data = $submission->getData();
      //var_dump($data); die();
      $file = File::load($data['uploads'][0]);
      $rows[] = [
        'name' => $data['published_name'],
        'submission' => $file->getFileUri(),
      ];
    }

    $output['table'] = [
      '#type' => 'table',
      '#header' => $header_table,
      '#rows' => $rows,
      '#empty' => 'No public submissions',
    ];

    return $output;
  }


  /**
   * Get the status text of the consultation.
   */
  public function getStatusMessage() {
    if ($this->isSubmissionsNowPublic()) {
      return 'Submissions now public';
    }
    elseif ($this->isNotStarted()) {
      return 'Consultation period not open';
    }
    elseif ($this->getDaysRemaining() <= 0) {
      return 'Now under review';
    }
    else {
      return 'Have your say';
    }
  }

  /**
   * Get the progress message of the status bar.
   */
  public function getProgressMessage() {
    if ($this->isSubmissionsNowPublic()) {
      return 'Closed';
    }
    elseif ($this->isNotStarted()) {
      return 'Starts in ' . round(($this->getDateStart('U') - time()) / 86400) . ' days';
    }
    elseif ($this->isFinished()) {
      return 'Closed';
    }
    else {
      die('x');
      return 'In progress';
    }
  }


  /**
   * Get the status class of the consultation.
   */
  public function getClasses() {
    $classes = [];

    if ($this->isSubmissionsNowPublic()) {
      $classes[] = 'cons-submissions-public';
    }
    elseif ($this->isSubmissionsEnabled()) {
      $classes[] = 'cons-submissions-enabled';
    }
    else {
      $classes[] = 'cons-submissions-hidden';
    }

    if ($this->isNotStarted()) {
      $classes[] = 'cons-progress-none';
    }
    elseif ($this->isSubmissionsNowPublic()) {
      $classes[] = 'cons-progress-closed';
    }
    elseif ($this->isFinished()) {
      $classes[] = 'cons-progress-closed';
    }
    else {
      $classes[] = 'cons-progress-open';
    }

    return $classes;
  }

  public function isFormHidden() {}

  /**
   * Has the consultation period started.
   */
  public function isNotStarted() {
    return (bool) (time() < $this->startDate->format('U'));
  }

  /**
   * Has the consultation period started.
   */
  public function isFinished() {
    return (bool) $this->getDaysRemaining() <= 0;
  }

  // @phpcs:ignore
  public function isSubmissionsEnabled() {
    return (bool) $this->node->field_cons_formal_subs_enabled->value;
  }

  public function isSubmissionsNowPublic() {
    return (bool) $this->node->field_cons_formal_subs_public->value;
  }


}
