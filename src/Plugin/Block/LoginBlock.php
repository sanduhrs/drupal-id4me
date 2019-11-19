<?php

namespace Drupal\id4me\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\FormBuilder;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an id4me login block.
 *
 * @Block(
 *   id = "id4me_login",
 *   admin_label = @Translation("ID4me login"),
 *   category = @Translation("Authentication")
 * )
 */
class LoginBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The immutable configuration object.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $config;

  /**
   * The form builder.
   *
   * @var \Drupal\Core\Form\FormBuilder
   */
  protected $formBuilder;

  /**
   * LoginBlock constructor.
   *
   * @param array $configuration
   *   The configuration.
   * @param string $plugin_id
   *   The plugin id.
   * @param string $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   * @param \Drupal\Core\Form\FormBuilder $form_builder
   *   The form builder.
   */
  public function __construct(
      array $configuration,
      $plugin_id,
      $plugin_definition,
      ConfigFactoryInterface $config_factory,
      FormBuilder $form_builder
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->config = $config_factory->get('id4me');
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('config.factory'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    if ($account->isAnonymous()) {
      return AccessResult::allowed()
        ->addCacheContexts([
          'user.roles:anonymous',
        ]);
    }
    return AccessResult::forbidden();
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'show_link' => $this->t('Show login link'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['show_link'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show login link'),
      '#default_value' => $this->configuration['show_link'],
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['show_link'] = $form_state->getValue('show_link');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = $this->formBuilder->getForm('Drupal\id4me\Form\LoginForm');
    $form['identifier']['#size'] = 15;
    if ($this->configuration['show_link']) {
      $form['login'] = [
        '#type' => 'link',
        '#title' => $this->t('Log in with Drupal'),
        '#url' => Url::fromRoute('user.login'),
        '#weight' => 100,
      ];
    }
    return $form;
  }

}
