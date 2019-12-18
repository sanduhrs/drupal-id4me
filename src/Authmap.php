<?php

namespace Drupal\id4me;

use Drupal\Core\Database\Connection;
use Drupal\user\Entity\User;

/**
 * Class Authmap.
 *
 * @package Drupal\id4me
 */
class Authmap {

  const DATABASE_TABLE = 'id4me_authmap';

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a Authmap object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A database connection.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Create a local to remote account association.
   *
   * @param object $account
   *   A user account object.
   * @param string $iss
   *   The issuer identifier.
   * @param string $sub
   *   The subject identifier.
   * @throws \Exception
   */
  public function createAssociation($account, $iss, $sub) {
    $fields = [
      'uid' => $account->id(),
      'iss' => $iss,
      'sub' => $sub,
    ];
    $this->connection->insert(self::DATABASE_TABLE)
      ->fields($fields)
      ->execute();
  }

  /**
   * Deletes a user's authmap entries.
   *
   * @param int $uid
   *   A user id.
   * @param string $iss
   *   A issuer identifier.
   */
  public function deleteAssociation($uid, $iss = '') {
    $query = $this->connection->delete(self::DATABASE_TABLE)
      ->condition('uid', $uid);
    if (!empty($iss)) {
      $query->condition('iss', $iss, '=');
    }
    $query->execute();
  }

  /**
   * Loads a user based on a sub-id and a login provider.
   *
   * @param string $iss
   *   The issuer identifier.
   * @param string $sub
   *   The subject identifier.
   *
   * @return \Drupal\Core\Entity\EntityInterface|false
   *   A user account object or FALSE
   */
  public function userLoadByIdentifier($iss, $sub) {
    $result = $this->connection->select(self::DATABASE_TABLE, 'a')
      ->fields('a', ['uid'])
      ->condition('iss', $iss, '=')
      ->condition('sub', $sub, '=')
      ->execute();
    foreach ($result as $record) {
      $account = User::load($record->uid);
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
    $result = $this->connection->select(self::DATABASE_TABLE, 'a')
      ->fields('a', ['iss', 'sub'])
      ->condition('uid', $account->id())
      ->execute();
    $authmaps = [];
    foreach ($result as $record) {
      $iss = $record->iss;
      $sub = $record->sub;
      $authmaps[$iss] = $sub;
    }
    return $authmaps;
  }

}
