<?php

namespace Drupal\consultation;

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

  /**
   * ConsultationNodeHelper constructor.
   */
  public function __construct(Node $node) {
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
    if (time() > $this->endDate->format('U')) {
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
    $seconds = $this->getDateEnd('U') - $this->getDateStart('U');
    $seconds_remaining = $this->getDateEnd('U') - time();
    $percentage = $seconds_remaining / $seconds * 100;
    $percentage = max(0, min(100, $percentage));
    return round($percentage);
  }

  // @phpcs:ignore
  public function getLateSubmissionUrl() {}

  // @phpcs:ignore
  public function getStatusMessage() {return '';}

  // @phpcs:ignore
  public function isFormHidden() {}

  // @phpcs:ignore
  public function isSubmissionEnabled() {}

}
