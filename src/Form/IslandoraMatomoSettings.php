<?php

namespace Drupal\islandora_matomo\Form;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class IslandoraMatomoSettings.
 */
class IslandoraMatomoSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'islandora_matomo_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['islandora_matomo.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('islandora_matomo.settings');

    $form['islandora_matomo_user_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Matomo User Token'),
      '#description' => $this->t('User token for authenticating to the Matomo API.'),
      '#default_value' => ( !empty($config->get('islandora_matomo_user_token')) ? $config->get('islandora_matomo_user_token') : '' ),
    ];

    $form['islandora_matomo_hits_or_visits'] = [
      '#type' => 'radios',
      '#title' => $this->t('Count hits or visits?'),
      '#description' => $this->t('Hits are the total number of times a page was loaded or a file downloaded, while visits counts all page loads and file downloads from the same visitor within 30 minutes as a single visit.'),
      '#default_value' => $config->get('islandora_matomo_hits_or_visits'),
      '#options' => array (
        0 => $this->t('Hits'),
	1 => $this->t('Visits'),
      ),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('islandora_matomo.settings');
    $config->set('islandora_matomo_user_token', $form_state->getValue('islandora_matomo_user_token'));
    $config->set('islandora_matomo_hits_or_visits', $form_state->getValue('islandora_matomo_hits_or_visits'));
    $config->save();
    parent::submitForm($form, $form_state);
  }

}
