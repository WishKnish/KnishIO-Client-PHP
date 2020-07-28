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
     * @var KnishIOClient
     */
	protected $knishIO;

    /**
     * @var string
     */
	protected static $query;


	/**
	 * Query constructor.
	 * @param KnishIOClient $knishIO
	 */
	public function __construct ( KnishIOClient $knishIO )
	{
	    $this->knishIO = $knishIO;
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
	public function createRequest ( array $variables = null, array $fields = null ) {

		// Default value of variables
		$this->variables = $this->compiledVariables( $variables );

		// Create a request
		return new Request(
			'POST',
			$this->url(),
			[ 'Content-Type' => 'application/json' ],
			json_encode( [ 'query' => $this->compiledQuery( $fields ), 'variables' => $this->variables, ] )
		);

	}


    /**
     * @return HttpClient
     */
	public function client ()
    {
        return $this->knishIO->client();
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
		$response = $this->client()->send( $this->request );

		if ( !( $this instanceof QueryAuthentication ) && $response->getStatusCode() === 401 ) {
            $this->knishIO->authentication();
            $response = $this->client()->send( $this->request );
        }

		// Create & save a response
		$this->response = $this->createResponseRaw( $response );

		// Return a response
		return $this->response;

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
			static::$query
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
	 * Create a response
	 *
	 * @param string $response
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
	public function url ()
    {
		return $this->knishIO->url();
	}

	/**
	 * @return mixed
	 */
	public function variables ()
    {
		return $this->variables;
	}

}
