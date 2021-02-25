<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use WishKnish\KnishIO\Client\HttpClient\HttpClient;
use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Response\Response;
use function GuzzleHttp\json_encode;

/**
 * Class Query
 * @package WishKnish\KnishIO\Client\Query
 */
abstract class Query
{
    /**
     * @var Client
     */
	protected $client;

	/**
	 * @var Request
	 */
	protected $request;

	/**
	 * @var Response
	 */
	protected $response;

    /**
     * @var array|null
     */
	protected $variables;

    /**
     * @var string
     */
	protected static $default_query;


	/**
	 * Query constructor.
	 * @param KnishIOClient $knishIO
	 */
	public function __construct ( HttpClientInterface $client, string $query = null )
	{
	    $this->client = $client;
		  $this->query = $query ?? static::$default_query;
	}


	/**
	 * @return Request
	 */
	public function request ()
	{
		return $this->request;
	}


	/**
	 * @return Response
	 */
	public function response ()
	{
		return $this->response;
	}


	/**
	 * Create new request
	 *
	 * @param array|null $variables
	 * @param array|null $fields
	 * @return RequestInterface
	 */
	public function createRequest ( array $variables = null, array $fields = null, array $headers = [] ) {

		// Default value of variables
		$this->variables = $this->compiledVariables( $variables );

		// Create a request
		return new Request(
			'POST',
			$this->url(),
			array_merge( $headers, [
				'Content-Type' => 'application/json',
				'x-auth-token' => $this->client->getAuthToken(),
			] ),
			json_encode( [ 'query' => $this->compiledQuery( $fields ), 'variables' => $this->variables, ] )
		);

	}


	/**
	 * @param array|null $variables
     * @param array $fields
	 * @return mixed
	 */
	public function execute ( array $variables = null, array $fields = null ) {

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
   * Debug info => get an url to execute GraphQL directly from it
   *
   * @param array|null $variables
   * @param array|null $fields
   *
   * @return string
   */
	public function getQueryUrl( string $name, $variables = null, array $fields = null ): string {

    // Compile variables
    if ( is_string( $variables ) ) {
      $variables = json_decode( trim( $variables ), true );
    }
    $variables = $this->compiledVariables( $variables );
    $variables = preg_replace( '#\"([^\"]+)\"\:#Usi', '$1:', json_encode($variables) );
    $variables = substr( $variables, 1, -1 );

    // Compile fields
    $fields = $fields ?? $this->fields;
    $fields = str_replace([', ', ' {'], [',', '{'], $this->compiledFields( $fields ));


    return $this->url(). str_replace([
        '@name', '@vars', '@fields',
      ],[
        $name, $variables, $fields,
      ], '?query={@name(@vars)@fields}');
  }


	/**
	 * @param array $fields
	 * @return mixed
	 */
	public function compiledQuery (array $fields = null)
	{
		// Fields
		if ($fields !== null) {
			$this->fields = $fields;
		}

		// Compiled query
		return str_replace(
			['@fields'],
			[$this->compiledFields($this->fields)],
			$this->query
		);
	}


	/**
	 * @param array $fields
	 * @return string
	 */
	protected function compiledFields (array $fields)
	{
		foreach ($fields as $key => $field) {
			if (is_array($field) ) {
				$fields[$key] = $key .' '. $this->compiledFields($field);
			}
		}
		return '{'.implode(', ', $fields).'}';
	}


	/**
	 * @param array|null $variables
	 * @return mixed
	 */
	public function compiledVariables ( array $variables = null )
	{
		return default_if_null( $variables, [] );
	}


	/**
	 * @param $response
	 * @return Response
	 */
	public function createResponse ( $response )
  {
		return new Response( $this, $response );
	}


	/**
	 * @param ResponseInterface $response
	 * @return Response
	 */
	public function createResponseRaw ( ResponseInterface $response )
	{
		return $this->createResponse( $response->getBody()->getContents() );
	}


	/**
	 * @return string|null
	 */
	public function url()
    {
		return $this->client->getUrl();
	}

	/**
	 * @return mixed
	 */
	public function variables (): array
  {
		return $this->variables;
	}

  /**
   * @return mixed
   */
	public function fields(): array
  {
	  return $this->fields;
  }

}
