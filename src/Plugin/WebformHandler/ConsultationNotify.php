<?php

namespace Drupal\consultation\Plugin\WebformHandler;

use Drupal\webform\Plugin\WebformHandler\EmailWebformHandler;
use Drupal\Core\Form\FormStateInterface;
use Drupal\webform\WebformSubmissionInterface;

/**
 * Form submission handler.
 *
 * @WebformHandler(
 *   id = "consultation_notify",
 *   label = @Translation("Consultation notify handler"),
 *   category = @Translation("Form Handler"),
 *   description = @Translation("Sends consultation submissions to configured email addresses."),
 *   cardinality = \Drupal\webform\Plugin\WebformHandlerInterface::CARDINALITY_UNLIMITED,
 *   results = \Drupal\webform\Plugin\WebformHandlerInterface::RESULTS_PROCESSED,
 *   submission = \Drupal\webform\Plugin\WebformHandlerInterface::SUBMISSION_OPTIONAL,
 *   tokens = TRUE,
 * )
 */
class ConsultationNotify extends EmailWebformHandler {

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildConfigurationForm($form, $form_state);

    $form['to']['to_mail']['to_mail']['#options']['Other']['consultation_email'] = $this->t('Consultation: The notification email configured on the consultation');
    return $this->setSettingsParents($form);
  }

  /**
   * {@inheritdoc}
   */
  public function getSummary() {
    return [
      '#theme' => 'webform_handler_email_summary',
      '#settings' => $this->configuration,
      '#handler' => $this,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function sendMessage(WebformSubmissionInterface $webform_submission, array $message) {
    $source = $webform_submission->getSourceEntity();

    if ($message['to_mail'] == 'consultation_email') {
      if ($source->hasField('field_cons_formal_subs_notify') && !$source->get('field_cons_formal_subs_notify')->isEmpty()) {
        $email = $source->field_cons_formal_subs_notify->value;
      }
      else {
        $consultation_settings = $this->configFactory->get('consultation.settings');
        $email = $consultation_settings->get('fallback_notify_email');
      }

      $message['to_mail'] = $email;
    }

    parent::sendMessage($webform_submission, $message);
  }

}
