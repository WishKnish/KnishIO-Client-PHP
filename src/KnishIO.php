<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use Exception;
use WishKnish\KnishIO\Client\Response\Response;

/**
 * Class KnishIO
 * @package WishKnish\KnishIO\Client
 *
 * @method createToken ( string $secret, string $token, $amount, array $metas = null )
 * @method transferToken ( string $fromSecret, $to, string $token, $amount, Wallet $remainderWallet = null )
 * @method getBalance ( string $code, string $token )
 * @method createIdentifier ( string $secret, $type, $contact, $code )
 * @method claimShadowWallet ( string $secret, string $token, Wallet $sourceWallet = null, Wallet $shadowWallet = null, $recipientWallet = null )
 * @method receiveToken ( string $secret, string $token, $value, $to, array $metas = null )
 *
 */
class KnishIO
{
	private static $url = 'https://wishknish.com/graphql';
	private static $client;
	private static $methods = [
		'getBalance', 'createToken', 'receiveToken', 'createIdentifier', 'claimShadowWallet', 'transferToken'
	];

	/**
	 * Get a KnishIOClient object
	 */
	private static function client ()
	{
		if ( !static::$client ) {
			static::$client = new KnishIOClient( static::$url );
		}
		return static::$client;
	}

	/**
	 * @return string
	 */
	public static function getUrl ()
	{
		return static::$url;
	}

	/**
	 * @param string $url
	 */
	public static function setUrl ( $url )
	{
		static::$url = $url;
		static::client()->setUrl( $url );
	}

	/**
	 * @param $name
	 * @param $arguments
	 * @return mixed
	 * @throws \Exception
	 */
	public static function __callStatic ( $name, $arguments )
	{
		// Check method
		if ( !\in_array( $name, static::$methods, true ) || !\method_exists( static::client(), $name ) ) {
			throw new \Exception( 'Method KnishIOClient::' . $name . ' is not a query method.' );
		}

		// Execute & get a response
		$response = \call_user_func_array( [ static::client(), $name ], $arguments );
		if ( !$response instanceof Response ) {
			throw new \Exception( 'Method KnishIOClient::' . $name . ' has not provide a valid response.' );
		}

		// Get a response payload
		return $response->payload();
	}

}
