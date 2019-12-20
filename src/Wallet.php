<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use desktopd\SHA3\Sponge as SHA3;
use BI\BigInteger;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Strings;

/**
 * Class Wallet
 * @package WishKnish\KnishIO\Client
 *
 * @property string $position
 * @property string $token
 * @property string $key
 * @property string $address
 * @property int|float $balance
 * @property array $molecules
 * @property string $bundle
 * @property string $privkey
 * @property string $pubkey
 *
 */
class Wallet
{

	/**
	 * @return string
	 */
	public static function generateBatchId () {
		return Strings::randomString( 64 );
	}

	/**
	 * @param string $code
	 * @return bool
	 */
	public static function isBundleHash ($code) {
		return (mb_strlen($code) === 64);
	}


	/**
	 * Wallet constructor.
	 *
	 * @param string $secret
	 * @param string $token
	 * @param string|null $position
	 * @param integer $saltLength
	 * @throws \Exception
	 */
	public function __construct ( $secret = null, $token = 'USER', $position = null, $saltLength = 64 )
	{
		$this->position = $position ?: Strings::randomString( $saltLength );
		$this->token = $token;
		$this->balance = 0;
		$this->molecules = [];
		$this->batchId = null;

		if ( $secret ) {
			$this->key = static::generateWalletKey( $secret, $token, $this->position );
			$this->address = static::generateWalletAddress( $this->key );
			$this->bundle = Crypto::generateBundleHash( $secret );
			$this->privkey = $this->getMyEncPrivateKey();
			$this->pubkey = $this->getMyEncPublicKey();
		}
	}

	/**
	 * @param string $key
	 * @return string
	 * @throws \Exception
	 */
	protected static function generateWalletAddress ( $key )
	{

		$digestSponge = SHA3::init( SHA3::SHAKE256 );

		foreach ( Strings::chunkSubstr( $key, 128 ) as $idx => $fragment ) {

			$workingFragment = $fragment;

			foreach ( \range( 1, 16 ) as $_ ) {

				$workingFragment = \bin2hex(
					SHA3::init( SHA3::SHAKE256 )
						->absorb( $workingFragment )
						->squeeze( 64 )
				);

			}

			$digestSponge->absorb( $workingFragment );

		}

		return \bin2hex(
			SHA3::init( SHA3::SHAKE256 )
				->absorb( \bin2hex( $digestSponge->squeeze( 1024 ) ) )
				->squeeze( 32 )
		);

	}

	/**
	 * Derives a private key for encrypting data with this wallet's key
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getMyEncPrivateKey ()
	{

		return Crypto::generateEncPrivateKey( $this->key );

	}

	/**
	 * Dervies a public key for encrypting data for this wallet's consumption
	 *
	 * @return string
	 * @throws \Exception
	 */
	public function getMyEncPublicKey ()
	{

		return Crypto::generateEncPublicKey( $this->getMyEncPrivateKey() );

	}

	/**
	 * Creates a shared key by combining this wallet's private key and another wallet's public key
	 *
	 * @param $otherPublicKey
	 * @return string
	 * @throws \Exception
	 */
	public function getMyEncSharedKey ( $otherPublicKey )
	{

		return Crypto::generateEncSharedKey( $this->getMyEncPrivateKey(), $otherPublicKey );

	}

	/**
	 * Uses the current wallet's private key to decrypt the given message
	 *
	 * @param string $message
     * @param string|null $otherPublicKey
	 * @return array|null
	 * @throws \Exception
	 */
	public function decryptMyMessage ( $message, $otherPublicKey = null )
	{

        if ( $otherPublicKey === null) {

            $target = Crypto::decryptMessage( $message, $this->getMyEncPublicKey() );

        }
        else {

            $target = Crypto::decryptMessage(
                $message,
                Crypto::generateEncPublicKey( $this->getMyEncSharedKey( $otherPublicKey ) )
            );

            if ( $target === null ) {

                $target = Crypto::decryptMessage( $message, $otherPublicKey );

            }

        }

		return $target;

	}

	/**
	 * @param string $secret
	 * @param string $token
	 * @param string $position
	 * @return string
	 * @throws \Exception
	 */
	public static function generateWalletKey ( $secret, $token, $position )
	{

		// Converting secret to bigInt
		$bigIntSecret = static::toBigInteger($secret);

		// Adding new position to the user secret to produce the indexed key
		$indexedKey = $bigIntSecret->add( static::toBigInteger($position) );

		// Hashing the indexed key to produce the intermediate key
		$intermediateKeySponge = SHA3::init( SHA3::SHAKE256 )
			->absorb( $indexedKey->toString( 16 ) );

		if ( $token !== '' ) {

			$intermediateKeySponge
				->absorb( $token );

		}

		// Hashing the intermediate key to produce the private key
		return \bin2hex(
			SHA3::init( SHA3::SHAKE256 )
				->absorb( \bin2hex(
					$intermediateKeySponge
						->squeeze( 1024 )
				) )->squeeze( 1024 )
		);

	}

	/**
	 * To BigInteger
	 *
	 * @param $value
	 * @return BigInteger
	 */
	public static function toBigInteger ($value)
	{
		return new BigInteger( $value, 16 );
	}

}

