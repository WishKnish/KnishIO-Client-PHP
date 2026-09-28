<?php
/*
        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Tests;

use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WishKnish\KnishIO\Client\HttpClient\HttpClient;

/**
 * Offline HTTP client: records every request and answers from a queue of canned GraphQL bodies.
 * It extends HttpClient because KnishIOClient stores its client in a `HttpClient`-typed property.
 */
final class RecordingHttpClient extends HttpClient {

  /** @var RequestInterface[] */
  public array $requests = [];

  /** @var string[] */
  private array $bodies;

  public function __construct ( string $uri, array $bodies ) {
    parent::__construct( $uri );
    $this->bodies = $bodies;
  }

  public function send ( RequestInterface $request, array $options = [] ): ResponseInterface {
    $this->requests[] = $request;
    if ( !$this->bodies ) {
      throw new \RuntimeException( 'RecordingHttpClient: unexpected request #' . count( $this->requests ) . ': ' . (string) $request->getBody() );
    }
    return new Response( 200, [ 'Content-Type' => 'application/json' ], array_shift( $this->bodies ) );
  }
}
