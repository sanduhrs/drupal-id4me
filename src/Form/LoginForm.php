<?php

namespace Drupal\id4me\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a ID4me form.
 */
class LoginForm extends FormBase implements ContainerInjectionInterface {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'id4me_login';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Enter your Identifier'),
      '#required' => TRUE,
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Login with ID4me'),
    ];
    $form['#attached']['library'][] = 'id4me/id4me';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\id4me\Id4meService $id4meService */
    $id4meService = \Drupal::service('id4me');
    $id4meService
      ->setIdentifier($form_state->getValue('identifier'))
      ->discover()
      ->register();
    $response = $id4meService->authorize();
    $form_state->setResponse($response);
  }

}
