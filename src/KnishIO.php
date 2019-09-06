<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use GuzzleHttp\Client;
use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\libraries\Crypto;

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
                'bundleHash' => mb_strlen( ( string ) $code ) === 64 ? $code : Crypto::generateBundleHash( $code ),
                'token' => $token,
            ]
        );

        if ( isset( $response[ 'data' ] ) && isset( $response[ 'data' ][ 'Balance' ] ) ) {
            $balance = $response[ 'data' ][ 'Balance' ];
            $wallet = new Wallet( $code, $balance[ 'tokenSlug' ] );
            $wallet->address = $balance[ 'address' ];
            $wallet->position = $balance[ 'position' ];
            $wallet->balance = $balance[ 'amount' ];
            $wallet->bundle = $balance[ 'bundleHash' ];
            unset( $wallet->key );
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
		$molecule->initTokenCreation( new Wallet( $secret ), new Wallet( $secret, $token ), $amount, $metas );
		$molecule->sign( $secret );

		if ( Molecule::verify( $molecule ) ) {
			$response = static::request( static::$query[ 'molecule' ], [ 'molecule' => $molecule, ] );
			return \array_intersect_key(
				$response[ 'data' ][ 'ProposeMolecule' ] ?: [ 'status' => 'rejected', 'reason' => 'Invalid response from server', ],
				\array_flip( [ 'reason', 'status', ] )
			);
		}

		return [ 'status' => 'rejected', 'reason' => 'The address of the molecule is not correctly generated', ];
	}

	/**
	 * @param string $fromSecret
	 * @param string $toSecret
	 * @param string $token
	 * @param int|float $amount
	 * @return array
	 * @throws \Exception|\ReflectionException|InvalidResponseException
	 */
	public static function transferToken ( $fromSecret, $toSecret, $token, $amount )
	{
		$fromWallet = static::getBalance( $fromSecret, $token );

		if ( null === $fromWallet || $amount > $fromWallet->balance ) {
			return [ 'status' => 'rejected', 'reason' => 'The transfer amount cannot be greater than the sender\'s balance', ];
		}

		$toWallet = static::getBalance( $toSecret, $token ) ?: new Wallet( $toSecret, $token );
		$molecule = new Molecule();
		$molecule->initValue( $fromWallet, $toWallet, new Wallet( $fromSecret, $token ), $amount );
		$molecule->sign( $fromSecret );

		if ( Molecule::verify( $molecule ) ) {
			$response = static::request( static::$query[ 'molecule' ], [ 'molecule' => $molecule, ] );
			return \array_intersect_key(
				$response[ 'data' ][ 'ProposeMolecule' ] ?: [ 'status' => 'rejected', 'reason' => 'Invalid response from server', ],
				\array_flip( [ 'reason', 'status', ] )
			);
		}

		return [ 'status' => 'rejected', 'reason' => 'The address of the molecule is not correctly generated', ];
	}

	/**
	 * @return Client
	 */
	private static function getClient ()
	{
		if ( !( static::$client instanceof Client ) ) {
			static::$client = new Client( [
				'base_uri'    => static::$url,
				'verify'      => false,
				'http_errors' => false,
				'headers'     => [ 'User-Agent' => 'KnishIO/0.1', 'Accept' => 'application/json', ]
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
		$response = static::getClient()->post( null, [ 'json' => [ 'query' => $query, 'variables' => $variables, ] ] );
		$responseJson = \json_decode( $response->getBody()->getContents(), true );

		if ( null === $responseJson ) {
			throw new InvalidResponseException();
		}

		return $responseJson;
	}
}
