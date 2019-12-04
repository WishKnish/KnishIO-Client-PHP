<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Response;

use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Query\Query;

/**
 * Class Response
 * @package WishKnish\KnishIO\Client\Query
 */
class Response
{
	protected $query;
	protected $response;
	protected $payload;
	protected $dataKey;

	/**
	 * Response constructor.
	 * @param string $json
	 */
	public function __construct(Query $query, string $json)
	{
		// Set a query
		$this->query = $query;

		// Json decode
		$this->origin_response = $this->response = \json_decode( $json, true );

		// No-json response - error
		if ( $this->response === null ) {
			throw new InvalidResponseException();
		}
	}


	/**
	 * Get a response
	 *
	 * @return mixed
	 */
	public function data () : ?array {
		if (!array_has($this->response, $this->dataKey) ) {
			throw new InvalidResponseException();
		}
		return array_get($this->response, $this->dataKey);
	}


	/**
	 * Get a payload
	 *
	 * @return
	 */
	public function payload () {
		return null;
	}


	/**
	 * @return Query
	 */
	public function query () : Query {
		return $this->query;
	}

}