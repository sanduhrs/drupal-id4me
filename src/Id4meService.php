<?php

namespace Drupal\id4me;

use Drupal\Core\Routing\TrustedRedirectResponse;
use Drupal\Core\Url;
use GuzzleHttp\Client;
use Id4me\RP\Service;
use Id4me\RP\Model\ClaimRequestList;
use Id4me\RP\Model\ClaimRequest;

/**
 * Id4meService service.
 */
class Id4meService {

  /**
   * The Id4me service facade.
   *
   * @var \Id4me\RP\Service
   */
  protected $id4Me;

  /**
   * The user's identifier.
   *
   * @var string
   */
  protected $identifier;

  /**
   * The OpenID Config Data.
   *
   * @var \Id4me\RP\Model\OpenIdConfig
   */
  protected $openidConfig;

  /**
   * Te authority name.
   *
   * @var string
   */
  protected $authorityName;

  /**
   * The OpenId Client Data.
   *
   * @var \Id4me\RP\Model\Client
   */
  protected $client;

  /**
   * The state token.
   *
   * @var string
   */
  protected $state;

  /**
   * The auth tokens.
   *
   * @var AuthorizationTokens
   */
  protected $authorizationTokens;

  /**
   * Id4meService constructor.
   */
  public function __construct() {
    $this->id4Me = new Service(
      new HttpClient(new Client())
    );
  }

  /**
   * Set identifier.
   *
   * @param string $identifier
   *   The user's identifier.
   *
   * @return $this
   */
  public function setIdentifier($identifier) {
    $this->identifier = $identifier;
    return $this;
  }

  /**
   * Get identifier.
   *
   * @return string
   *   The user's identifier.
   */
  public function getIdentifier() {
    return $this->identifier;
  }

  /**
   * Set state.
   *
   * @param string $state
   *   The state identifier.
   *
   * @return $this
   */
  public function setState($state) {
    $this->state = $state;
    return $this;
  }

  /**
   * Get state.
   *
   * @return string
   *   The state identifier.
   */
  public function getState() {
    return $this->state;
  }

  /**
   * Discover the Id4me service.
   *
   * @return $this
   *
   * @throws \Id4me\RP\Exception\InvalidOpenIdDomainException
   *   An InvalidOpenIdDomainException exception.
   * @throws \Id4me\RP\Exception\OpenIdDnsRecordNotFoundException
   *   An invalid OpenIdDnsRecordNotFoundException exception.
   */
  public function discover() {
    $this->authorityName = $this->id4Me->discover($this->identifier);
    return $this;
  }

  /**
   * Register with the Id4me service.
   *
   * @return $this
   *
   * @throws \Id4me\RP\Exception\InvalidAuthorityIssuerException
   *   An invalid InvalidAuthorityIssuerException exception.
   */
  public function register() {
    if ($cache = \Drupal::cache('id4me')->get($this->authorityName)) {
      $this->client = $cache->data;
    }
    else {
      $this->openidConfig = $this->id4Me->getOpenIdConfig($this->authorityName);
      $this->client = $this->id4Me->register(
        $this->openidConfig,
        \Drupal::config('system.site')->get('name'),
        Url::fromUserInput('/id4me/authorize', ['absolute' => TRUE])->toString()
      );
      \Drupal::cache('id4me')->set($this->authorityName, $this->client);
    }
    return $this;
  }

  /**
   * Authorize with the Id4me service.
   *
   * @return \Drupal\Core\Routing\TrustedRedirectResponse
   *   A trusted redirect response.
   */
  public function authorize() {
    $this->state = StateToken::create();
    $_SESSION['id4me_' . $this->state] = [
      'authorityName' => $this->authorityName,
      'client' => serialize($this->client),
      'identifier' => $this->identifier,
      'openidConfig' => serialize($this->openidConfig),
    ];

    $authorizationUrl = $this->id4Me->getAuthorizationUrl(
      $this->openidConfig,
      $this->client->getClientId(),
      $this->identifier,
      $this->client->getActiveRedirectUri(),
      $this->state,
      NULL,
      new ClaimRequestList(
        new ClaimRequest('preferred_username', TRUE, 'To initiate a local account'),
        new ClaimRequest('email', TRUE, 'To initiate a local account')
      )
    );
    return new TrustedRedirectResponse($authorizationUrl);
  }

  /**
   * Get authorization tokens.
   *
   * @param string $code
   *   The authorization code.
   *
   * @return \Id4me\RP\Model\AuthorizationTokens
   *   The authorization tokens.
   *
   * @throws \Id4me\RP\Exception\InvalidAuthorityIssuerException
   *   An invalid InvalidAuthorityIssuerException exception.
   * @throws \Id4me\RP\Exception\InvalidIDTokenException
   *   An invalid InvalidIDTokenException exception.
   */
  public function getAuthorizationTokens($code) {
    $this->openidConfig = unserialize($_SESSION['id4me_' . $this->state]['openidConfig']);
    $this->client = unserialize($_SESSION['id4me_' . $this->state]['client']);
    $this->authorizationTokens = $this->id4Me->getAuthorizationTokens($this->openidConfig, $code, $this->client);
    return $this->authorizationTokens;
  }

  /**
   * Get user info.
   *
   * @return \Id4me\RP\Model\UserInfo
   *   The user info.
   */
  public function getUserInfo() {
    return $this->id4Me->getUserInfo(
      $this->openidConfig,
      $this->client,
      $this->authorizationTokens
    );
  }

}
