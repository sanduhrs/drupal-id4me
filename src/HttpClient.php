<?php

namespace Drupal\id4me;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use GuzzleHttp\Client;
use Id4me\RP\HttpClient as HttpClientInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class HttpClient.
 *
 * @package Drupal\id4me
 */
class HttpClient implements HttpClientInterface, ContainerInjectionInterface {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * HttpClient constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   Th real HTTP client.
   */
  public function __construct(Client $http_client) {
    $this->httpClient = $http_client;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client')
    );
  }

  /**
   * A GET request.
   *
   * @param string $url
   *   The request URL.
   * @param array $headers
   *   The request headers.
   *
   * @return \Psr\Http\Message\StreamInterface
   *   A message stream.
   */
  public function get($url, array $headers = []) {
    $response = $this->httpClient->get($url, ['headers' => $headers]);
    return $response->getBody();
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
   * @return \Psr\Http\Message\StreamInterface
   *   A message stream.
   */
  public function post($url, $body, array $headers = []) {
    $response = $this->httpClient->post($url, [
      'headers' => $headers,
      'body' => $body,
    ]);
    return $response->getBody();
  }

}
