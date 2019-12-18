<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use GuzzleHttp\Client;
use WishKnish\KnishIO\Client\Response\Response;

/**
 * Class Query
 * @package WishKnish\KnishIO\Client\Query
 */
abstract class Query
{
	protected $client;
	protected $url;

	protected static $query;

	/**
	 * Query constructor.
	 * @param Client $client
	 * @param string $url
	 */
	public function __construct(Client $client, string $url = null)
	{
		$this->url = $url;
		$this->client = $client;
	}


	/**
	 * @param array $variables
	 * @return mixed
	 */
	public function execute (array $variables = []) : Response {

		// Make a request
		$response = $this->client->post( $this->url, [
			'json' => [
				'query'     => static::$query,
				'variables' => $variables,
			]
		] );

		// Return a response
		return $this->createResponse($response->getBody()->getContents());
	}


	/**
	 * Create a response
	 *
	 * @param string $response
	 * @return Response
	 */
	public function createResponse (string $response) {
		return new Response($this, $response);
	}



}
