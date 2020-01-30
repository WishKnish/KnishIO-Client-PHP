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
use WishKnish\KnishIO\Client\Libraries\Base58;

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
 * @property string|null $batchId
 * @property string|null $characters
 *
 */
class Wallet
{
    /**
     * @var string|null
     */
    public $batchId = null;

    /**
     * @var array
     */
    public $molecules = [];

    /**
     * @var int|float
     */
    public $balance = 0;

    /**
     * @var string|null
     */
    public $address = null;

    /**
     * @var string|null
     */
    public $bundle = null;

    /**
     * @var string|null
     */
    public $key = null;

    /**
     * @var string|null
     */
    public $pubkey = null;

    /**
     * @var string|null
     */
    private $privkey = null;

	/**
	 * @return string
	 */
	public static function generateBatchId () {
		return Strings::randomString( 64 );
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
	public function __construct ( $secret = null, $token = 'USER', $position = null, $saltLength = 64, $characters = null )
	{
		$this->position = $position ?: Strings::randomString( $saltLength );
		$this->token = $token;
        $this->characters = \defined(Base58::class . '::' . $characters ) ? $characters : null;

		if ( $secret ) {
			$this->key = static::generateWalletKey( $secret, $token, $this->position );
			$this->address = static::generateWalletAddress( $this->key );
			$this->bundle = Crypto::generateBundleHash( $secret );
			$this->getMyEncPrivateKey();
			$this->getMyEncPublicKey();
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
	 * @return string|null
	 * @throws \Exception
	 */
	public function getMyEncPrivateKey ()
	{

        if ( $this->privkey === null ) {

            $this->privkey = Crypto::generateEncPrivateKey( $this->key, $this->characters );

        }

		return $this->privkey;

	}

	/**
	 * Dervies a public key for encrypting data for this wallet's consumption
	 *
	 * @return string|null
	 * @throws \Exception
	 */
	public function getMyEncPublicKey ()
	{

	    if ( $this->pubkey === null ) {

            $this->pubkey = Crypto::generateEncPublicKey( $this->getMyEncPrivateKey(), $this->characters );

        }

		return $this->pubkey;

	}

	/**
	 * Creates a shared key by combining this wallet's private key and another wallet's public key
	 *
	 * @param string $key
	 * @return string|null
	 * @throws \Exception
	 */
	public function getMyEncSharedKey ( $key )
	{

		return Crypto::generateEncSharedKey( $this->getMyEncPrivateKey(), $key, $this->characters );

	}

    /**
     * @param array $message
     * @param string|null $key
     * @return string|null
     * @throws \ReflectionException
     */
    public function encryptMyMessage ( array $message, $key = null )
    {

        return Crypto::encryptMessage(
            $message,
            ( $key === null ) ? $this->getMyEncPrivateKey() : $this->getMyEncSharedKey( $key ),
            $this->characters
        );

    }

	/**
	 * Uses the current wallet's private key to decrypt the given message
	 *
	 * @param string $message
     * @param string|null $key
	 * @return array|null
	 * @throws \Exception
	 */
	public function decryptMyMessage ( $message, $key = null )
	{

        if ( $key === null ) {

            $target = Crypto::decryptMessage( $message, $this->getMyEncPublicKey(), $this->characters );

        }
        else {

            $target = Crypto::decryptMessage(
                $message,
                Crypto::generateEncPublicKey( $this->getMyEncSharedKey( $key ), $this->characters ),
                $this->characters
            );

            if ( $target === null ) {

                $target = Crypto::decryptMessage( $message, $key, $this->characters );

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
		$bigIntSecret = new BigInteger( $secret, 16 );

		// Adding new position to the user secret to produce the indexed key
		$indexedKey = $bigIntSecret->add( new BigInteger( $position, 16 ) );

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

}
