<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Libraries;

use desktopd\SHA3\Sponge as SHA3;
use WishKnish\KnishIO\Client\Libraries\Base58Static as B58;
use WishKnish\KnishIO\Client\Libraries\Base58;

/**
 * Class Crypto
 * @package WishKnish\KnishIO\Client\Libraries
 */
class Crypto
{

    private static $sodium = false;

	/**
	 * Generates a secret based on an optional seed
	 *
	 * @param null $seed
	 * @param int $length
	 * @return string
	 * @throws \Exception
	 */
	public static function generateSecret ( $seed = null, $length = 2048 )
	{

		return \in_array( $seed, [ null, '' ], true ) ?
			Strings::randomString( $length ) :
			\bin2hex( SHA3::init( SHA3::SHAKE256 )
				->absorb( $seed )
				->squeeze( $length / 4 ) );

	}

	/**
	 * Hashes the user secret to produce a bundle hash
	 *
	 * @param string $secret
	 * @return string
	 * @throws \Exception
	 */
	public static function generateBundleHash ( $secret )
	{

		return \bin2hex( SHA3::init( SHA3::SHAKE256 )
			->absorb( $secret )
			->squeeze( 32 ) );

	}

	/**
	 * Encrypts the given message or data with the recipient's public key
	 *
	 * @param array|object $message
     * @param string $key
     * @param string|null $characters
	 * @return string|null
	 * @throws \Exception|\ReflectionException
	 */
	public static function encryptMessage ( $message, $key, $characters = null )
	{

        static::includedSodium();

        B58::$options[ 'characters' ] = \defined(Base58::class . '::' . $characters ) ?
            \constant(Base58::class . '::' . $characters ) : Base58::GMP;
        $target = null;

        if ( $key !== null ) {

            $sharedPair = \sodium_crypto_box_keypair();
            $nonce = \random_bytes( SODIUM_CRYPTO_BOX_NONCEBYTES );
            $bin = $nonce .
                \sodium_crypto_box_secretkey( $sharedPair ) .
                \sodium_crypto_box(
                    \json_encode( $message ),
                    $nonce,
                    \sodium_crypto_box_keypair_from_secretkey_and_publickey(
                        B58::decode( $key ),
                        \sodium_crypto_box_publickey( $sharedPair )
                    )
                );

            $target = B58::encode( $bin );

            \sodium_memzero( $nonce );
            \sodium_memzero( $sharedPair );
            \sodium_memzero( $bin );

        }

        return $target;

	}

	/**
	 * Uses the given private key to decrypt an encrypted message
	 *
	 * @param string $ciphertext
     * @param string $key
     * @param string|null $characters
	 * @return array|null
     * @throws \ReflectionException
	 */
	public static function decryptMessage ( $ciphertext, $key, $characters = null )
	{

        static::includedSodium();

        B58::$options[ 'characters' ] = \defined(Base58::class . '::' . $characters ) ?
            \constant(Base58::class . '::' . $characters ) : Base58::GMP;

        $cipher = !empty( $ciphertext ) ? B58::decode( $ciphertext ) : '';
        $target = null;

        if ( !empty( $cipher ) &&
            $key !== null &&
            ( SODIUM_CRYPTO_BOX_NONCEBYTES + SODIUM_CRYPTO_BOX_SECRETKEYBYTES ) < \mb_strlen( $cipher, '8bit' ) ) {

            $nonce = \mb_substr( $cipher, 0, SODIUM_CRYPTO_BOX_NONCEBYTES, '8bit' );
            $shared = \mb_substr( $cipher, SODIUM_CRYPTO_BOX_NONCEBYTES, SODIUM_CRYPTO_BOX_SECRETKEYBYTES, '8bit' );
            $message = \mb_substr( $cipher, SODIUM_CRYPTO_BOX_NONCEBYTES + SODIUM_CRYPTO_BOX_SECRETKEYBYTES, null, '8bit' );

            $target = \json_decode(
                \sodium_crypto_box_open(
                    $message,
                    $nonce,
                    \sodium_crypto_box_keypair_from_secretkey_and_publickey(
                        $shared,
                        B58::decode( $key )
                    )
                ),
                true
            );

            \sodium_memzero( $nonce );
            \sodium_memzero( $shared );
            \sodium_memzero( $message );
            \sodium_memzero( $cipher );

        }

		return $target;

	}

	/**
	 * Derives a private key for encrypting data with the given key
	 *
	 * @param string|null $key
     * @param string|null $characters
	 * @return string|null
	 * @throws \Exception|\ReflectionException
	 */
	public static function generateEncPrivateKey ( $key = null, $characters = null )
	{
        static::includedSodium();

        B58::$options[ 'characters' ] = \defined(Base58::class . '::' . $characters ) ?
            \constant(Base58::class . '::' . $characters ) : Base58::GMP;

		return $key !== null ?
            B58::encode(
                \sodium_crypto_box_secretkey(
                    SHA3::init( SHA3::SHAKE256 )
                        ->absorb( $key )
                        ->squeeze( SODIUM_CRYPTO_BOX_KEYPAIRBYTES )
                )
		    ) : null;

	}

	/**
	 * Derives a public key for encrypting data for this wallet's consumption
	 *
	 * @param string|null $key
     * @param string|null $characters
	 * @return string|null
     * @throws \ReflectionException
	 */
	public static function generateEncPublicKey ( $key = null, $characters = null )
	{

        static::includedSodium();

        B58::$options[ 'characters' ] = \defined(Base58::class . '::' . $characters ) ?
            \constant(Base58::class . '::' . $characters ) : Base58::GMP;

		return $key !== null ? B58::encode( \sodium_crypto_box_publickey_from_secretkey( B58::decode( $key ) ) ) : null;

	}

	/**
	 * Creates a shared key by combining this wallet's private key and another wallet's public key
	 *
	 * @param string|null $privateKey
	 * @param string|null $publicKey
     * @param string|null $characters
	 * @return string|null
     * @throws \ReflectionException
	 */
	public static function generateEncSharedKey ( $privateKey = null, $publicKey = null, $characters = null )
	{

	    static::includedSodium();

        B58::$options[ 'characters' ] = \defined(Base58::class . '::' . $characters ) ?
            \constant(Base58::class . '::' . $characters ) : Base58::GMP;

		return ( $privateKey !== null && $publicKey !== null ) ?
            B58::encode( \sodium_crypto_scalarmult( B58::decode( $privateKey ), B58::decode( $publicKey ) ) ) :
            null;

	}

    /**
     * @throws \ReflectionException
     */
    private static function includedSodium ()
    {

        if ( ! \extension_loaded( 'sodium' ) && ! static::$sodium ) {
            Sodium::libsodium2sodium();
            static::$sodium = true;
        }

    }

}
