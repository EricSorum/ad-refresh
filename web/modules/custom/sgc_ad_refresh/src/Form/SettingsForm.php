<?php

namespace Drupal\sgc_ad_refresh\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure example settings for this site.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'sgc_ad_refresh.settings';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'sgc_ad_refresh_admin_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      static::SETTINGS,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    $config = $this->config(static::SETTINGS);
    $items = $config->get('items') ?? NULL;
    $page_exclusions = json_decode($items)->page_exclusions ?? NULL;

    if (!empty($items) && $items != NULL) {
      $items = json_decode($items, TRUE);
    }
    
    $form['status'] = array(
      '#type' => 'select',
      '#options' => array(
        0 => t('No'),
        1 => t('Yes'),
      ),
      '#title' => t('Status'),
      '#description' => t('Should ad refresh be enabled?'),
      '#default_value' => $status ?? 1,
    );
    $form['description'] = [
      '#type' => 'item',
      '#markup' => $this->t('This module allows users to configure how frequently each ad refreshes.'),
    ];    
    $form['details'] = [
      '#type' => 'details',
      '#title' => $this->t('GTM Tags'),
      '#open' => TRUE,
    ];
    
    $tag_arr = get_dfp_tags();

    foreach ($tag_arr as $tag) {
      $form['details'][$tag] = [
        '#type' => 'details',
        '#title' => $tag,
        '#open' => FALSE,
        '#tree' => TRUE,
      ];
      $form['details'][$tag]['status'] = [
        '#type' => 'select',
        '#options' => array(
          0 => t('No'),
          1 => t('Yes'),
        ),
        '#title' => t('Tag Status'),
        '#description' => t('Should ad refresh be enabled on this Google Tag ad slot?'),
        '#default_value' => $items[$tag]['status'] ?? 0,
        '#group' => $tag,
      ];      
      $form['details'][$tag]['interval'] = [
        '#type' => 'number',
        '#step' => 5,
        '#min' => 10,
        '#max' => 120,
        '#description' => $this->t('Enter the number of seconds between refresh <br /> Min: 10 | Max: 120 | Increments of 5'),
        '#title' =>$this->t('Refresh interval'),
        '#default_value' => $items[$tag]['interval'] ?? 20,
        '#group' => $tag,
      ];
    }

    $form['container']['page_exclusions'] = array(
      '#type' => 'textarea',
      '#title' => t('Page Exclusions'),
      '#description' => t('Utilize page exclusions to stop Ad Refresh from firing under certain conditions.'),
      '#default_value' => $page_exclusions ?? "/admin*\r/batch*\r/node/add*\r/node/*/edit\r/node/*/delete\r/node/*/layout\r/taxonomy/term/*/edit\r/taxonomy/term/*/layout\ruser/*/edit\r/user*\r/user/*/cancel*\r/user/*/layout",
      '#rows' => 7,
    );
    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config(static::SETTINGS);
    $values = $form_state->getValues();
    $config->set('items', json_encode($values));
    $config->save();
    parent::submitForm($form, $form_state);
  }
}



