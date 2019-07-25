<?php

namespace Drupal\consultation;

use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\Core\FileTransfer\FileTransfer;
use Drupal\webform\WebformSubmissionInterface;

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
    $end = $this->getDateEnd('U');
    $start = $this->getDateStart('U');
    $now = time();

    if ($end < $now) {
      return 0;
    }
    elseif ($start < $now && $now < $end) {
      return ceil(($end - $now) / 86400);
    }
    elseif ($end < $now) {
      return 0;
    }
  }

  public function getDaysUntil() {
    $end = $this->getDateEnd('U');
    $start = $this->getDateStart('U');
    $now = time();

    if ($now < $start) {
      return ceil(($start - $now) / 86400);
    }

    return 0;
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

      return 100 - round($percentage);
    }
  }

  // @phpcs:ignore
  public function getLateSubmissionUrl() {}


  public function getWebform() {
    if (!$this->node->field_cons_webform->isEmpty()) {
      return $this->node->field_cons_webform->first()->entity;
    }
  }

  /**
   * @param $title
   *   Allows caller to pass a title if not being used in a block scenario. Bit hacky.
   *
   * @return array|bool
   *   Render array. False if no public submissions OR submissions are not public yet.
   */
  public function getPublicSubmissionsDisplay($title = FALSE) {
    if (!$this->isSubmissionsNowPublic()) {
      return FALSE;
    }


    $webform = $this->getWebform();
    $webform_id = $webform->id();
    $output = [];

    // @todo inject t.
    $header_table = [
      'name' => 'Submitted by',
      'submission' => 'Submission',
    ];

    $rows = [];
    // The results do not run an access check because we determine this in the loop statement.
    $query = \Drupal::entityQuery('webform_submission')
      ->condition('webform_id', $webform_id)
      ->accessCheck(FALSE);
    $result = $query->execute();

    $storage = \Drupal::entityTypeManager()->getStorage('webform_submission');
    $submissions = $storage->loadMultiple($result);
    $has_results = FALSE;
    /** @var WebformSubmissionInterface $submission */
    foreach ($submissions as $submission) {
      $source_entity = $submission->getSourceEntity();

      if ($source_entity && $source_entity->id() == $this->node->id()) {
        $data = $submission->getData();
        if (isset($data['uploads'][0]) && self::isSubmissionPublic($submission)) {

          $file = File::load($data['uploads'][0]);
          if ($file && $file->getEntityTypeId() == 'file') {
            $render_file = [
              '#theme' => 'file_link',
              '#file' => $file,
            ];

            // Add this public submission.
            $has_results = TRUE;
            $rows[] = [
              'name' => self::getSubmissionPublicName($submission),
              'submission' => ['data' => $render_file],
            ];
          }
        }

      }
    }

    if (!$has_results) {
      // Nothing to show.
      return FALSE;
    }

    $output['public_submissions'] = [];
    if ($title) {
      // This is not ideal hard-coding. Should be done in a template?
      // Currently this is just a render array with no tempalte.
      $output['public_submissions']['title'] = ['#markup' => '<h2 class="content-anchor" id="consultation-submissions">' . $title . '</h2>'];
    }
    $output['public_submissions']['table'] = [
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
      return 'Open';
    }
  }

  /**
   * Get the status text of the consultation.
   */
  public function getStatusCode() {
    if ($this->isSubmissionsNowPublic()) {
      return 'public';
    }
    elseif ($this->isNotStarted()) {
      return 'upcoming';
    }
    elseif ($this->getDaysRemaining() <= 0) {
      return 'closed';
    }
    else {
      return 'open';
    }
  }

  /**
   * Get the progress message of the status bar.
   */
  public function getProgressMessage() {
    if ($this->isNotStarted()) {
      return 'Starts in ' . round(($this->getDateStart('U') - time()) / 86400) . ' days';
    }
    elseif ($this->isFinished()) {
      if ($this->isSubmissionsExtended()) {
        return 'Submission period extended';
      }
      else {
        return 'Closed';
      }
    }
    else {
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

    $classes[] = 'consultation-' . $this->getStatusCode();

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

  public function isSubmissionsExtended() {
    return (bool) $this->node->field_cons_late_subs->value;
  }

  public function isSubmissionsEnabled() {
    return (bool) $this->node->field_cons_formal_subs_enabled->value;
  }

  public function isSubmissionsOpenOrExtended() {
    $return = (bool) ((!$this->isNotStarted() && !$this->isFinished()) || $this->isSubmissionsExtended());
    return $return;
  }

  public function isSubmissionsNowPublic() {
    return (bool) $this->node->field_cons_formal_subs_public->value;
  }

  public static function isSubmissionFilePublic($submission) {
    $data = $submission->getData();
    if (self::isSubmissionPublic($submission)) {

      // This check ensures that the submitter opts-in, ie. the admin must
      // intentionally override the privacy setting of the submission.
      if (isset($data[CONSULTATION_PRIVATE_ELEMENT]) && !$data[CONSULTATION_PRIVATE_ELEMENT]) {
        return TRUE;
      }
    }

    return FALSE;
  }

  public static function isSubmissionPublic($submission) {
    $data = $submission->getData();
    if ($data[CONSULTATION_DISPLAY_SUBMISSION_ELEMENT] == CONSULTATION_DISPLAY_SUBMISSION_ALLOWED) {
      return TRUE;
    }

    return FALSE;
  }

  public static function getSubmissionPublicName($submission) {
    $data = $submission->getData();
    $element = CONSULTATION_ANONYMOUS_ELEMENT;
    if (isset($data[$element]) && empty($data[$element])) {
      return $data['published_name'];
    }

    return 'Anonymous';
  }

  /**
   * @return array
   */
  public function themeBuildProgressBar() {
    return [
      '#theme' => 'consultation_progress_bar',
      '#date_start' => $this->getDateStart(),
      '#date_end' => $this->getDateEnd(),
      '#percentage_complete' => $this->getPercentageComplete(),
      '#days_remain' => $this->getDaysRemaining(),
      '#days_total' => $this->getDaysTotal(),
      '#days_until' => $this->getDaysUntil(),
      '#open_or_extended' => $this->isSubmissionsOpenOrExtended(),
      '#progress_message' => $this->getProgressMessage(),
      '#status_message' => $this->getStatusMessage(),
      '#status_code' => $this->getStatusCode(),
    ];
  }

}
