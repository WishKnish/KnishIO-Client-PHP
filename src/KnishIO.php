<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use GuzzleHttp\Client;
use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Query\QueryBalance;

/**
 * Class KnishIO
 * @package WishKnish\KnishIO\Client
 */
class KnishIO
{
	private static $url = 'https://wishknish.com/graphql';
	private static $client;
	private static $query = [
		'molecule' => 'mutation( $molecule: MoleculeInput! ) { ProposeMolecule( molecule: $molecule, ) { molecularHash, height, depth, status, reason, reasonPayload, createdAt, receivedAt, processedAt, broadcastedAt } }',
        'wallet' => 'query($address: String, $walletBundle: String, $token: String) { Wallet(address: $address, bundleHash: $walletBundle, token: $token) { bundleHash, address, position, amount, tokenSlug, createdAt, } }',
		'balance'  => 'query( $address: String, $bundleHash: String, $token: String, $position: String ) { Balance( address: $address, bundleHash: $bundleHash, token: $token, position: $position ) { address, bundleHash, tokenSlug, batchId, position, amount, createdAt } }',
	];

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
	 * @param string $code
	 * @return bool
	 */
	protected static function isBundleHash (string $code) : bool {
		return (mb_strlen($code) === 64);
	}


	/**
	 * @param string $code
	 * @param string $token
	 * @return Wallet|null
	 * @throws \Exception|\ReflectionException|InvalidResponseException
	 */
	public static function getBalance ( $code, $token )
	{
		// Create a query
		$query = new QueryBalance(static::getClient(), static::getUrl());

		// Execute the query
		$response = $query->execute([
			// If bundle hash came, we pass it, otherwise we consider it a secret
			'bundleHash' => static::isBundleHash( $code ) ? $code : Crypto::generateBundleHash( $code ),
			'token'      => $token,
		]);

		// Return a payload
		return $response->payload();

		/*

		$wallet = null;
		$response = static::request(
			static::$query[ 'balance' ],
			[
				// If bundle hash came, we pass it, otherwise we consider it a secret
				'bundleHash' => static::isBundleHash( $code ) ? $code : Crypto::generateBundleHash( $code ),
				'token'      => $token,
			]
		);
		//dump ($response);

		// @todo add errors handling: check the 'errors' key from a response

		if ( isset( $response[ 'data' ] ) && isset( $response[ 'data' ][ 'Balance' ] ) ) {
			$balance = $response[ 'data' ][ 'Balance' ];
			if ( $balance[ 'tokenSlug' ] === $token ) {

				// If the balance is NULL - it is a shadow wallet
				if ($balance[ 'position' ] === null) {
					$wallet = new WalletShadow(
						$balance['bundleHash'], $balance['tokenSlug'], $balance['batchId']
					);
				}

				// Regular wallet
				else {
					$wallet = new Wallet( null, $balance[ 'tokenSlug' ] );
					$wallet->address = $balance[ 'address' ];
					$wallet->position = $balance[ 'position' ];
					$wallet->bundle = $balance[ 'bundleHash' ];
					$wallet->batchId = $balance[ 'batchId' ];
				}

				// Bind other data
				$wallet->balance = $balance[ 'amount' ];
			}
		}
		return $wallet;

		*/
	}

	/**
	 * @param string $secret
	 * @param string $token
	 * @param int|float $amount
	 * @param array $metas
	 * @return array
	 * @throws \Exception|\ReflectionException|InvalidResponseException
	 */
	public static function createToken ( $secret, $token, $amount, array $metas = [] )
	{
		// Recipient wallet
		$recipientWallet = new Wallet( $secret, $token );

		// For stackable token - create a batch ID
		if (array_get($metas, 'fungibility') === 'stackable') {
			$recipientWallet->batchId = Wallet::generateBatchId();
		}

		$molecule = new Molecule();
        $fromWallet = new Wallet( $secret );
        $remainderWallet = new Wallet ( $secret );
		$molecule->initTokenCreation( $fromWallet, $recipientWallet, $remainderWallet, $amount, $metas );
		$molecule->sign( $secret );

		// Check the molecule
		$molecule->check();

		$response = static::request( static::$query[ 'molecule' ], [ 'molecule' => $molecule, ] );
		return \array_intersect_key(
			$response[ 'data' ][ 'ProposeMolecule' ] ?: [
				'status' => 'rejected',
				'reason' => 'Invalid response from server',
			],
			\array_flip( [ 'reason', 'status', ] )
		);
	}


	/**
	 * Bind shadow wallet
	 *
	 * @param $bundleHash
	 * @param $token
	 */
	public static function claimShadowWallet ($secret, $token, $shadowWallet = null, $recipentWallet = null) {

		// From wallet (a USER wallet, used for signing)
		$fromWallet = new Wallet( $secret );

		// Shadow wallet (to get a Batch ID & balance from it)
		$shadowWallet = $shadowWallet ?? static::getBalance( $secret, $token );
		if ( $shadowWallet === null || !$shadowWallet instanceof WalletShadow ) {
			return [
				'status' => 'rejected',
				'reason' => 'The shadow wallet does not exist',
			];
		}

		// Create a recipient wallet to generate new position & address
		$recipientWallet = new Wallet( $secret, $token );

		// Meta with address & position to the shadow wallet
		$metas = [
			'walletAddress' 	=> $recipientWallet->address,
			'walletPosition'	=> $recipientWallet->position,
		];

		// Wallet for user remainder atom
		$remainderWallet = new Wallet ( $secret );

		// Create & sign a molecule
		$molecule = new Molecule();
		$molecule->initShadowWalletClaim( $fromWallet, $shadowWallet, $remainderWallet, $metas );
//		$molecule->bundle = $shadowWallet->bundle;
		$molecule->sign( $secret );


		// Check the molecule
		$molecule->check();

		$response = static::request( static::$query[ 'molecule' ], [ 'molecule' => $molecule, ] );
		dd ($response);
		return \array_intersect_key(
			$response[ 'data' ][ 'ProposeMolecule' ] ?: [
				'status' => 'rejected',
				'reason' => 'Invalid response from server',
			],
			\array_flip( [ 'reason', 'status', ] )
		);

	}


	/**
	 * @param string $fromSecret
	 * @param string|Wallet $to
	 * @param string $token
	 * @param int|float $amount
	 * @return array
	 * @throws \Exception|\ReflectionException|InvalidResponseException
	 */
	public static function transferToken ( $fromSecret, $to, $token, $amount, Wallet $remainderWallet = null)
	{
		$fromWallet = static::getBalance( $fromSecret, $token );
		if ( $fromWallet === null || $fromWallet->balance < $amount ) {
			return [
				'status' => 'rejected',
				'reason' => 'The transfer amount cannot be greater than the sender\'s balance',
			];
		}

		// Is a non-stackable token & $to is a bundle hash? Error, bundle hash can be passed only for a shadow wallet
		// @todo add logic to get target wallet by bundle hash for non-stackable tokens
		if (!$fromWallet->batchId && is_string($to) && static::isBundleHash($to) ) {
			return [
				'status' => 'rejected',
				'reason' => 'The recipient cannot be a bundle hash for a non-stackable token',
			];
		}


		// If this wallet is assigned, if not, try to get a valid wallet
		$toWallet = $to instanceof Wallet ? $to : static::getBalance( $to, $token );

		// Remainder wallet
		$remainderWallet = $remainderWallet ?? new Wallet( $fromSecret, $token );


		// --- BEGIN: Batch ID initialization
		if ($fromWallet->batchId) {

			// Has already a shadow wallet? Use batch ID from it
			if ($toWallet !== null) {
				$batchId = $toWallet->batchId;
			}

			// Has a remainder?
			else if (($fromWallet->balance - $amount) > 0) {
				$batchId = Wallet::generateBatchId();
			}

			// Has no remainder?: use batch ID from the source wallet
			else {
				$batchId = $fromWallet->batchId;
			}

			// If $to parameter is a bundle hash & shadow wallet does not exist: create it
			if ($toWallet === null) {
				$toWallet = new WalletShadow( $to, $token, $batchId );
			}

			// Set remainder wallet batch ID
			$remainderWallet->batchId = $batchId;
		}
		// --- END: Batch ID initialization


		// If the wallet is not transferred in a variable and the user does not have a balance wallet,
		// then create a new one for him
		if ( $toWallet === null ) {
			$toWallet = new Wallet( $to, $token );
		}

		// Create & sign a molecule
		$molecule = new Molecule();
		$molecule->initValue( $fromWallet, $toWallet, $remainderWallet, $amount );
		$molecule->sign( $fromSecret );

		// Check the molecule
		$molecule->check($fromWallet);

		$response = static::request( static::$query[ 'molecule' ], [ 'molecule' => $molecule, ] );
		return \array_intersect_key(
			$response[ 'data' ][ 'ProposeMolecule' ] ?: [
				'status' => 'rejected',
				'reason' => 'Invalid response from server',
			],
			\array_flip( [
				'reason',
				'status',
			] )
		);
	}


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
	 * @param string $query
	 * @param array $variables
	 * @return array
	 * @throws InvalidResponseException
	 */
	private static function request ( $query, $variables )
	{
		$response = static::getClient()->post( null, [
			'json' => [
				'query'     => $query,
				'variables' => $variables,
			]
		] );

		$responseJson = \json_decode( $response->getBody()->getContents(), true );

		if ( $responseJson === null ) {
			throw new InvalidResponseException();
		}

		return $responseJson;
	}
}
