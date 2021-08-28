<?php
/*
                               (
                              (/(
                              (//(
                              (///(
                             (/////(
                             (//////(                          )
                            (////////(                        (/)
                            (////////(                       (///)
                           (//////////(                      (////)
                           (//////////(                     (//////)
                          (////////////(                    (///////)
                         (/////////////(                   (/////////)
                        (//////////////(                  (///////////)
                        (///////////////(                (/////////////)
                       (////////////////(               (//////////////)
                      (((((((((((((((((((              (((((((((((((((
                     (((((((((((((((((((              ((((((((((((((
                     (((((((((((((((((((            ((((((((((((((
                    ((((((((((((((((((((           (((((((((((((
                    ((((((((((((((((((((          ((((((((((((
                    (((((((((((((((((((         ((((((((((((
                    (((((((((((((((((((        ((((((((((
                    ((((((((((((((((((/      (((((((((
                    ((((((((((((((((((     ((((((((
                    (((((((((((((((((    (((((((
                   ((((((((((((((((((  (((((
                   #################  ##
                   ################  #
                  ################# ##
                 %################  ###
                 ###############(   ####
                ###############      ####
               ###############       ######
              %#############(        (#######
             %#############           #########
            ############(              ##########
           ###########                  #############
          #########                      ##############
        %######

        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client\Query;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;
use WishKnish\KnishIO\Client\Response\Response;
use function GuzzleHttp\json_encode;

/**
 * Class Query
 * @package WishKnish\KnishIO\Client\Query
 */
abstract class Query {
  /**
   * @var Client
   */
  protected $client;

  /**
   * @var Request
   */
  protected Request $request;

  /**
   * @var Response
   */
  protected Response $response;

  /**
   * @var array|null
   */
  protected ?array $variables;

  /**
   * @var string
   */
  protected static string $default_query;

  /**
   * @var array
   */
  protected array $fields;

  /**
   * Query constructor.
   *
   * @param HttpClientInterface $client
   * @param string|null $query
   */
  public function __construct ( HttpClientInterface $client, string $query = null ) {
    $this->client = $client;
    $this->query = $query ?? static::$default_query;
  }

  /**
   * @return Request
   */
  public function request (): Request {
    return $this->request;
  }

  /**
   * @return Response
   */
  public function response (): Response {
    return $this->response;
  }

  /**
   * Create new request
   *
   * @param array|null $variables
   * @param array|null $fields
   *
   * @return RequestInterface
   */
  public function createRequest ( array $variables = null, array $fields = null, array $headers = [] ) {

    // Default value of variables
    $this->variables = $this->compiledVariables( $variables );

    // Create a request
    return new Request( 'POST', $this->uri(), array_merge( $headers, [ 'Content-Type' => 'application/json', 'x-auth-token' => $this->client->getAuthToken(), ] ), json_encode( [ 'query' => $this->compiledQuery( $fields ), 'variables' => $this->variables, ] ) );

  }

  /**
   * @param array|null $variables
   * @param array|null $fields
   *
   * @return Response
   * @throws GuzzleException
   */
  public function execute ( array $variables = null, array $fields = null ): Response {

    // Set a request
    $this->request = $this->createRequest( $variables, $fields );

    // Make a request
    $response = $this->client->send( $this->request );

    // Create & save a response
    $this->response = $this->createResponseRaw( $response );

    // Return a response
    return $this->response;

  }

  /**
   * Debug info => get an uri to execute GraphQL directly from it
   *
   * @param string $name
   * @param array|string|null $variables
   * @param array|null $fields
   *
   * @return string
   */
  public function getQueryUri ( string $name, $variables = null, array $fields = null ): string {

    // Compile variables
    if ( is_string( $variables ) ) {
      $variables = json_decode( trim( $variables ), true );
    }
    $variables = $this->compiledVariables( $variables );
    $variables = preg_replace( '#\"([^\"]+)\":#U', '$1:', json_encode( $variables ) );
    $variables = substr( $variables, 1, -1 );

    // Compile fields
    $fields = $fields ?? $this->fields;
    $fields = str_replace( [ ', ', ' {' ], [ ',', '{' ], $this->compiledFields( $fields ) );

    return $this->uri() . str_replace( [ '@name', '@vars', '@fields', ], [ $name, $variables, $fields, ], '?query={@name(@vars)@fields}' );
  }

  /**
   * @param array|null $fields
   *
   * @return array|string|string[]
   */
  public function compiledQuery ( array $fields = null ) {
    // Fields
    if ( $fields !== null ) {
      $this->fields = $fields;
    }

    // Compiled query
    return str_replace( [ '@fields' ], [ $this->compiledFields( $this->fields ) ], $this->query );
  }

  /**
   * @param array $fields
   *
   * @return string
   */
  protected function compiledFields ( array $fields ): string {
    foreach ( $fields as $key => $field ) {
      if ( is_array( $field ) ) {
        $fields[ $key ] = $key . ' ' . $this->compiledFields( $field );
      }
    }
    return '{' . implode( ', ', $fields ) . '}';
  }

  /**
   * @param array|null $variables
   *
   * @return array
   */
  public function compiledVariables ( array $variables = null ): array {
    return default_if_null( $variables, [] );
  }

  /**
   * @param string $response
   *
   * @return Response
   */
  public function createResponse ( string $response ): Response {
    return new Response( $this, $response );
  }

  /**
   * @param ResponseInterface $response
   *
   * @return Response
   */
  public function createResponseRaw ( ResponseInterface $response ): Response {
    return $this->createResponse( $response->getBody()
        ->getContents() );
  }

  /**
   * @return string|null
   */
  public function uri (): ?string {
    return $this->client()
        ->getUri();
  }

  /**
   * @return array
   */
  public function variables (): array {
    return $this->variables;
  }

  /**
   * @return HttpClientInterface
   */
  public function client (): HttpClientInterface {
    return $this->client;
  }

  /**
   * @return array
   */
  public function fields (): array {
    return $this->fields;
  }

}
