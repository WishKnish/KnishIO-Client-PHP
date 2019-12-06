<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use GuzzleHttp\Client;
use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\WalletShadowException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Query\QueryBalance;
use WishKnish\KnishIO\Client\Query\QueryTokenCreate;
use WishKnish\KnishIO\Client\Query\QueryTokenTransfer;
use WishKnish\KnishIO\Client\Query\QueryWalletClaim;
use WishKnish\KnishIO\Client\Response\Response;

/**
 * Class KnishIO
 * @package WishKnish\KnishIO\Client
 */
class KnishIO
{
	private static $url = 'https://wishknish.com/graphql';
	private static $client;



	/**
	 * @return Client
	 */
	private static function getClient ()
	{
		if ( ! ( static::$client instanceof Client ) ) {
			static::$client = new Client( [
				'base_uri'    => static::$url,
				'verify'      => false,
				'http_errors' => false,
				'headers'     => [
					'User-Agent' => 'KnishIO/0.1',
					'Accept'     => 'application/json',
				]
			] );
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
		static::$client = null;
	}


	/**
	 * @param $code
	 * @param $token
	 * @return Response
	 * @throws \Exception
	 */
	public static function getBalance ( $code, $token ) : Response
	{
		// Create a query
		$query = new QueryBalance(static::getClient(), static::getUrl());

		// If bundle hash came, we pass it, otherwise we consider it a secret
		$bundleHash = Wallet::isBundleHash( $code ) ? $code : Crypto::generateBundleHash( $code );

		// Execute the query
		return $query->execute([
			'bundleHash' => $bundleHash,
			'token'      => $token,
		]);
	}


	/**
	 * @param $secret
	 * @param $token
	 * @param $amount
	 * @param array $metas
	 * @return Response
	 * @throws \ReflectionException
	 */
	public static function createToken ( $secret, $token, $amount, array $metas = [] ) : Response
	{
		$query = new QueryTokenCreate(static::getClient(), static::getUrl());

		// Init a molecule
		$query->initMolecule ( $secret, $token, $amount, $metas );

		// Execute a query
		return $query->execute();
	}


	/**
	 * Bind shadow wallet
	 *
	 * @param $bundleHash
	 * @param $token
	 */
	public static function claimShadowWallet ($secret, $token, $shadowWallet = null, $recipientWallet = null) : Response {

		// From wallet (a USER wallet, used for signing)
		$fromWallet = new Wallet( $secret );

		// Shadow wallet (to get a Batch ID & balance from it)
		$shadowWallet = $shadowWallet ?? static::getBalance( $secret, $token )->payload();
		if ( $shadowWallet === null || !$shadowWallet instanceof WalletShadow ) {
			throw new WalletShadowException();
		}


		// Create a query
		$query = new QueryWalletClaim(static::getClient(), static::getUrl());

		// Init a molecule
		$query->initMolecule ( $secret, $fromWallet, $shadowWallet, $token, $recipientWallet );

		// Execute a query
		return $query->execute();
	}


	/**
	 * @param string $fromSecret
	 * @param string|Wallet $to
	 * @param string $token
	 * @param int|float $amount
	 * @return array
	 * @throws \Exception|\ReflectionException|InvalidResponseException
	 */
	public static function transferToken ( $fromSecret, $to, $token, $amount, Wallet $remainderWallet = null) : Response
	{
		// Get a from wallet
		$fromWallet = static::getBalance( $fromSecret, $token )->payload();
		if ( $fromWallet === null || $fromWallet->balance < $amount ) {
			throw new TransferBalanceException('The transfer amount cannot be greater than the sender\'s balance');
		}

		// If this wallet is assigned, if not, try to get a valid wallet
		$toWallet = $to instanceof Wallet ? $to : static::getBalance( $to, $token )->payload();
		if ($toWallet === null) {

			// If from wallet has a batchID => recipient is a shadow wallet
			if ($fromWallet->batchId) {
				$toWallet = new WalletShadow( $to, $token );
			}

			// If the wallet is not transferred in a variable and the user does not have a balance wallet,
			// then create a new one for him
			else {
				$toWallet = new Wallet( $to, $token );
			}
		}



		// Create a query
		$query = new QueryTokenTransfer(static::getClient(), static::getUrl());

		// Init a molecule
		$query->initMolecule ( $fromSecret, $fromWallet, $toWallet, $token, $amount, $remainderWallet );

		// Execute a query
		return $query->execute();
	}


}
