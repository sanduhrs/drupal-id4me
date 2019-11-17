<?php

/**
 * Class Authmap.
 *
 * @package Drupal\id4me
 */
class Authmap {

  const DATABASE_TABLE = 'id4me_authmap';

  /**
   * Create a local to remote account association.
   *
   * @param object $account
   *   A user account object.
   * @param string $client_name
   *   The client name.
   * @param string $sub
   *   The remote subject identifier.
   *
   * @throws \Exception
   */
  public function createAssociation($account, $client_name, $sub) {
    $fields = [
      'uid' => $account->uid,
      'client_name' => $client_name,
      'sub' => $sub,
    ];
    db_insert(self::DATABASE_TABLE)
      ->fields(array_keys($fields), array_values($fields))
      ->execute();
  }

  /**
   * Deletes a user's authmap entries.
   *
   * @param int $uid
   *   A user id.
   * @param string $client_name
   *   A client name.
   */
  public function deleteAssociation($uid, $client_name = '') {
    $query = db_delete(self::DATABASE_TABLE)
      ->condition('uid', $uid);
    if (!empty($client_name)) {
      $query->condition('client_name', $client_name, '=');
    }
    $query->execute();
  }

  /**
   * Loads a user based on a sub-id and a login provider.
   *
   * @param string $sub
   *   The remote subject identifier.
   * @param string $client_name
   *   The client name.
   *
   * @return stdClass|false
   *   A user account object or FALSE
   */
  public function userLoadBySub($sub, $client_name) {
    $result = db_select(self::DATABASE_TABLE, 'a')
      ->fields('a', ['uid'])
      ->condition('client_name', $client_name, '=')
      ->condition('sub', $sub, '=')
      ->execute();
    foreach ($result as $record) {
      $account = user_load($record->uid);
      if (is_object($account)) {
        return $account;
      }
    }
    return FALSE;
  }

  /**
   * Get a list of external accounts connected to this Drupal account.
   *
   * @param object $account
   *   A Drupal user entity.
   *
   * @return array
   *   An array of 'sub' properties keyed by the client name.
   */
  public function getConnectedAccounts($account) {
    $result = db_select(self::DATABASE_TABLE, 'a')
      ->fields('a', ['client_name', 'sub'])
      ->condition('uid', $account->id())
      ->execute();
    $authmaps = [];
    foreach ($result as $record) {
      $client = $record->client_name;
      $sub = $record->sub;
      $authmaps[$client] = $sub;
    }
    return $authmaps;
  }

}
