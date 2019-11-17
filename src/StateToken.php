<?php

namespace Drupal\id4me;

/**
 * Class StateToken.
 *
 * @package Drupal\id4me
 */
class StateToken {

  /**
   * Creates a state token and stores it in the session for later validation.
   *
   * @return string
   *   A state token that later can be validated to prevent request forgery.
   *
   * @throws \Exception
   */
  public static function create() {
    $state = random_bytes_base64();
    $_SESSION['id4me_state'] = $state;
    return $state;
  }

  /**
   * Confirms anti-forgery state token.
   *
   * @param string $state_token
   *   The state token that is used for validation.
   *
   * @return bool
   *   Whether the state token matches the previously created one that is stored
   *   in the session.
   */
  public static function confirm($state_token) {
    return isset($_SESSION['id4me_state']) &&
      $state_token == $_SESSION['id4me_state'];
  }

}
