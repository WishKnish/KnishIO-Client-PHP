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

use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Request;
use JetBrains\PhpStorm\Pure;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Mutation\MutationProposeMolecule;
use WishKnish\KnishIO\Client\Response\Response;

/**
 * Class Query
 * @package WishKnish\KnishIO\Client\Query
 */
abstract class Query {
  /**
   * @var HttpClientInterface
   */
  protected HttpClientInterface $client;

  /**
   * @var string
   */
  protected string $query;

  /**
   * @var Request
   */
  protected Request $request;

  /**
   * @var Response
   */
  protected Response $response;

  /**
   * @var string
   */
  protected static string $default_query;

  /**
   * @var array|null
   */
  protected array $variables;

  /**
   * @var array
   */
  protected array $fields;

  /**
   * @var bool
   */
  protected bool $isMutation = false;

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
   * @param array|null $variables
   * @param array|null $fields
   * @param array $headers
   *
   * @return Request
   * @throws JsonException
   */
  public function createRequest ( array $variables = null, array $fields = null, array $headers = [] ): Request {

    // Default value of variables
    $this->variables = $this->compiledVariables( $variables ?? [] );

    // Create a request
    return new Request( 'POST', $this->uri(),
      array_merge( $headers, [ 'Content-Type' => 'application/json', 'x-auth-token' => $this->client->getAuthToken(), ] ),
      json_encode( [
        'query' => $this->compiledQuery( $fields ),
        'variables' => $this->variables,
      ], JSON_THROW_ON_ERROR )
    );
  }

  /**
   * @param array|null $variables
   * @param array|null $fields
   *
   * @return Response
   * @throws GuzzleException
   * @throws JsonException
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
   * @param string $json
   * !!! DEBUG FUNCTION
   *
   * @return string
   * @throws GuzzleException
   */
  public static function getProposeMoleculeUri ( string $json ): string {
    $client = new KnishIOClient( url() . '/graphql' );
    $molecule = Molecule::jsonToObject( $json );
    $query = $client->createMoleculeMutation( MutationProposeMolecule::class, $molecule );
    return $query->getQueryUri( 'ProposeMolecule' );
  }

  /**
   * Debug info => get an uri to execute GraphQL directly from it
   * !!! DEBUG FUCNTION
   *
   * @param string $name
   * @param array|string $variables
   * @param array|null $fields
   *
   * @return string
   * @throws JsonException
   */
  public function getQueryUri ( string $name, array|string $variables, array $fields = null ): string {

    // Compile variables
    if ( is_string( $variables ) ) {
      $variables = json_decode( trim( $variables ), true, 512, JSON_THROW_ON_ERROR );
    }
    $variables = $this->compiledVariables( $variables );
    $variables = preg_replace( '#\"([^\"]+)\":#U', '$1:', json_encode( $variables, JSON_THROW_ON_ERROR ) );
    $variables = substr( $variables, 1, -1 );

    // Compile fields
    $fields = $fields ?? $this->fields;
    $fields = str_replace( [ ', ', ' {' ], [ ',', '{' ], $this->compiledFields( $fields ) );

    return $this->uri() . str_replace(
        [ '@name', '@mutation', '@vars', '@fields', ],
        [ $name, ( $this->isMutation ? 'mutation' : '' ), $variables, $fields, ],
        '?query=@mutation{@name(@vars)@fields}'
      );
  }

  /**
   * @param array|null $fields
   *
   * @return array|string
   */
  public function compiledQuery ( array $fields = null ): array|string {
    // Overwrite default fields value
    if ( $fields ) {
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
   * @param array $variables
   *
   * @return array
   */
  public function compiledVariables ( array $variables ): array {
    return $variables;
  }

  /**
   * @param string $response
   *
   * @return Response
   * @throws JsonException
   */
  public function createResponse ( string $response ): Response {
    return new Response( $this, $response );
  }

  /**
   * @param ResponseInterface $response
   *
   * @return Response
   * @throws JsonException
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
