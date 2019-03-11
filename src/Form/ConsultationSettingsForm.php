<?php

namespace Drupal\consultation\Form;

/**
 * @file
 * Contains Drupal\consultation\Form\ConsultationSettingsForm.
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class ConsultationSettingsForm.
 */
class ConsultationSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'consultation.settings',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'consultation_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('consultation.settings');

    $form['enable_private'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Allow private submissions'),
      '#description' => $this->t('By checking this box you agree to the storage of potentially sensitive data on your govCMS site. For more information, please read the help page.'),
      '#default_value' => $config->get('enable_private'),
    ];

    $form['enable_disqus'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Disqus integration (informal discussion)'),
      '#description' => $this->t('Enable informal discussion on a per-Consultation basis (Disqus account required).'),
      '#default_value' => $config->get('enable_disqus'),
    ];

    $form['disqus_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Disqus shortname'),
      '#description' => $this->t('Disqus shortname.'),
      '#default_value' => $config->get('disqus_account'),
    ];

    $form['enable_twitter'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Enable Twitter integration'),
      '#description' => $this->t('Enable embedded Twitter discussion (Twitter account required).'),
      '#default_value' => $config->get('enable_twitter'),
    ];

    $form['twitter_account'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Twitter account id'),
      '#description' => $this->t('Twitter account id (leave out @ symbol).'),
      '#default_value' => $config->get('twitter_account'),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('enable_disqus') && empty($form_state->getValue('disqus_account'))) {
      $form_state->setErrorByName('disqus_account', $this->t('You must provide a Disqus shortname.'));
    }

    if ($form_state->getValue('enable_twitter') && empty($form_state->getValue('twitter_account'))) {
      $form_state->setErrorByName('twitter_account', $this->t('You must provide a Twitter username.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('consultation.settings')
      ->set('enable_private', $form_state->getValue('enable_private'))
      ->save();

    $this->config('consultation.settings')
      ->set('enable_disqus', $form_state->getValue('enable_disqus'))
      ->save();

    $this->config('consultation.settings')
      ->set('disqus_account', $form_state->getValue('disqus_account'))
      ->save();

    $this->config('consultation.settings')
      ->set('enable_twitter', $form_state->getValue('enable_twitter'))
      ->save();

    $this->config('consultation.settings')
      ->set('twitter_account', $form_state->getValue('twitter_account'))
      ->save();
  }

}
