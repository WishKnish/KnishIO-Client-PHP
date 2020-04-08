<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Request;
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
     * @var string|null
     */
	protected $url;
    /**
     * @var array|null
     */
	protected $variables;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var string
     */
	protected static $query;

	/**
	 * Query constructor.
	 * @param Client $client
	 * @param string|null $url
	 */
	public function __construct ( Client $client, $url = null )
	{
		$this->url = $url;
		$this->client = $client;
        $this->request = new Request( 'POST', $url, [ 'Content-Type' => 'application/json' ], json_encode( [] ) );
	}

    /**
     * @param array|null $variables
     * @return Request
     */
	protected function setRequest( array $variables = null )
    {
        // Default value of variables
        $this->variables = default_if_null( $variables, [] );

        $this->request = new Request(
            'POST',
            $this->url,
            [ 'Content-Type' => 'application/json' ],
            json_encode( [ 'query' => static::$query, 'variables' => $this->variables, ] )
        );

        return $this->request;
    }

    /**
     * @return Request
     */
    public function getRequest ()
    {
	    return $this->request;
    }

    /**
     * @param array $options
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send ( array $options = [] )
    {
        return $this->client->send( $this->getRequest(), $options );
    }

	/**
	 * @param array|null $variables
     * @param boolean $request
	 * @return mixed
	 */
	public function execute ( array $variables = null,  $request = false ) {

        $this->setRequest( $variables );

        if ( $request ) {
            return $this->getRequest();
        }

		// Make a request
		$response = $this->send();

		// Return a response
		return $this->createResponse( $response->getBody()->getContents() );
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
	 * @return string|null
	 */
	public function url ()
    {
		return $this->url;
	}

	/**
	 * @return mixed
	 */
	public function variables ()
    {
		return $this->variables;
	}

}
