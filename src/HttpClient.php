<?php

use Id4me\RP\HttpClient as HttpClientInterface;

/**
 * Class HttpClient.
 *
 * @package Drupal\id4me
 */
class HttpClient implements HttpClientInterface {

  /**
   * A GET request.
   *
   * @param string $url
   *   The request URL.
   * @param array $headers
   *   The request headers.
   *
   * @return string
   *   A message string.
   */
  public function get($url, array $headers = []) {
    $response = drupal_http_request($url, [
      'method' => 'GET',
      'headers' => $headers,
    ]);
    return $response->data;
  }

  /**
   * A POST request.
   *
   * @param string $url
   *   The request URL.
   * @param string $body
   *   The request body.
   * @param array $headers
   *   The request headers.
   *
   * @return string
   *   A message string.
   */
  public function post($url, $body, array $headers = []) {
    $response = drupal_http_request($url, [
      'method' => 'POST',
      'headers' => $headers,
      'data' => $body,
    ]);
    return $response->data;
  }

}
