<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Response;

use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Exception\UnauthenticatedException;
use WishKnish\KnishIO\Client\Query\Query;

/**
 * Class Response
 * @package WishKnish\KnishIO\Client\Query
 */
class Response
{
    /**
     * @var Query
     */
	protected $query;

    /**
     * @var array|null
     */
	protected $response;

	/**
	 * @var
	 */
	protected $payload;

    /**
     * @var string
     */
	protected $dataKey;


	/**
	 * Response constructor.
     * @param Query $query
	 * @param string $json
	 */
	public function __construct ( Query $query, $json )
	{
		// Set a query
		$this->query = $query;

		// Origin response
		$this->origin_response = $json;

		// Json decode
		$this->response = \json_decode( $json, true );

		// No-json response - error
		if ( $this->response === null ) {
			throw new InvalidResponseException();
		}

		// Catch exceptions
		if (array_has ($this->response, 'exception') ) {

			// Exception error
			$message = array_get($this->response, 'message');

			// Custom exceptions
			if ( stripos( $message, 'Unauthenticated' ) !== false ) {
				throw new UnauthenticatedException ( $message );
			}

			// Default exception
			throw new InvalidResponseException( $message );
		}

		$this->init ();
	}


	/**
	 *
	 */
	public function init () {

	}


	/**
	 * Get a response
	 *
	 * @return mixed
	 */
	public function data ()
    {

		// For the root class
		if ( !$this->dataKey ) {
			return $this->response;
		}

		// Check key & return custom data from the response
		if ( !array_has( $this->response, $this->dataKey ) ) {
			throw new InvalidResponseException();
		}

		return array_get( $this->response, $this->dataKey );
	}


	/**
	 * @return mixed
	 */
	public function response ()
    {
		return $this->response;
	}


	/**
	 * Get a payload
	 *
	 * @return
	 */
	public function payload ()
    {
		return null;
	}


	/**
	 * @return Query
	 */
	public function query ()
    {
		return $this->query;
	}

}
