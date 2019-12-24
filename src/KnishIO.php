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
		'balance'  => 'query( $address: String, $bundleHash: String, $token: String, $position: String ) { Balance( address: $address, bundleHash: $bundleHash, token: $token, position: $position ) { address, bundleHash, tokenSlug, position, amount, createdAt } }',
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
	 * @param string $token
	 * @return Wallet|null
	 * @throws \Exception|\ReflectionException|InvalidResponseException
	 */
	public static function getBalance ( $code, $token )
	{
		$wallet = null;
		$response = static::request(
			static::$query[ 'balance' ],
			[
				// If bundle hash came, we pass it, otherwise we consider it a secret
				'bundleHash' => mb_strlen( ( string ) $code ) === 64 ? $code : Crypto::generateBundleHash( $code ),
				'token'      => $token,
			]
		);

		if ( isset( $response[ 'data' ] ) && isset( $response[ 'data' ][ 'Balance' ] ) ) {

			$balance = $response[ 'data' ][ 'Balance' ];

			if ( $balance[ 'tokenSlug' ] === $token ) {

				$wallet = new Wallet( null, $balance[ 'tokenSlug' ] );
                [
                    $wallet->address,
                    $wallet->position,
                    $wallet->balance,
                    $wallet->bundle,
                    $wallet->batchId,
                ] = \array_unpacking(
                    $balance,
                    'address',
                    'position',
                    'amount',
                    'bundleHash',
                    'batch_id'
                );

			}

		}

		return $wallet;
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
		$molecule = new Molecule();
        $fromWallet = new Wallet( $secret );
		$molecule->initTokenCreation( $fromWallet, new Wallet( $secret, $token ), $amount, $metas );
		$molecule->sign( $secret );

        $verify = CheckMolecule::verify( $molecule, $fromWallet );

        if ( $verify !== null ) {

            return $verify;

        }

		$response = static::request( static::$query[ 'molecule' ], [ 'molecule' => $molecule, ] );
		return \array_unpacking(
			$response[ 'data' ][ 'ProposeMolecule' ] ?: [
				'status' => 'rejected',
				'reason' => 'Invalid response from server',
			],
            'reason',
            'status'
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

		// If this wallet is assigned, if not, try to get a valid wallet
		$toWallet = $to instanceof Wallet ? $to : static::getBalance( $to, $token );

		// Remainder wallet
		$remainderWallet = $remainderWallet ?: new Wallet( $fromSecret, $token );

		// If the wallet is not transferred in a variable and the user does not have a balance wallet,
		// then create a new one for him
		if ( $toWallet === null ) {
			$toWallet = new Wallet( $to, $token );
		}

		$molecule = new Molecule();
		$molecule->initValue( $fromWallet, $toWallet, $remainderWallet, $amount );
		$molecule->sign( $fromSecret );

        $verify = CheckMolecule::verify( $molecule, $fromWallet );

        if ( $verify !== null ) {

            return $verify;

        }

		$response = static::request( static::$query[ 'molecule' ], [ 'molecule' => $molecule, ] );
		return \array_unpacking(
			$response[ 'data' ][ 'ProposeMolecule' ] ?: [
				'status' => 'rejected',
				'reason' => 'Invalid response from server',
			],
			'reason',
			'status'
        );
	}


	/**
	 * @param $fromSecret
	 * @param $toBundle
	 * @param $token
	 * @param $amount
	 * @return array
	 * @throws \ReflectionException
	 */
	public static function splitToken ($fromSecret, $toBundle, $token, $amount ) {

		// Get a from wallet from the DB
		$fromWallet = static::getBalance( $fromSecret, $token );
		if ( $fromWallet === null || $fromWallet->balance < $amount ) {
			return [
				'status' => 'rejected',
				'reason' => 'The transfer amount cannot be greater than the sender\'s balance',
			];
		}

		// Get a batch ID for the recipient wallet
		$batchId = ($fromWallet->balance - $amount) > 0 // has a remainder?
			? Wallet::generateBatchId()
			: $fromWallet->batchId;

		// To wallet
		$toWallet = new WalletShadow( $toBundle, $token, $batchId );

		// Remainder wallet
		$remainderWallet = new Wallet( $fromSecret, $token );
		$remainderWallet->batchId = $batchId;

		// Token transfering
		return static::transferToken($fromSecret, $toWallet, $token, $amount, $remainderWallet);
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
