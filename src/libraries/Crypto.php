<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\libraries;

use desktopd\SHA3\Sponge as SHA3;

/**
 * Class Crypto
 * @package WishKnish\KnishIO\Client\libraries
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
	 * @param string $recipientPrivateKey
	 * @return string
	 * @throws \Exception|\ReflectionException
	 */
	public static function encryptMessage ( $message, $recipientPrivateKey )
	{
        static::includedSodium();

		$sharedPair = \sodium_crypto_box_keypair();
		$nonce = \random_bytes( SODIUM_CRYPTO_BOX_NONCEBYTES );

		return \implode( "+", [
			\bin2hex(
				\sodium_crypto_box(
					\json_encode( $message ),
					$nonce,
					\sodium_crypto_box_keypair_from_secretkey_and_publickey(
						\hex2bin( $recipientPrivateKey ),
						\sodium_crypto_box_publickey( $sharedPair )
					)
				)
			),
			\bin2hex( \sodium_crypto_box_secretkey( $sharedPair ) ),
			\bin2hex( $nonce ),
		] );
	}

	/**
	 * Uses the given private key to decrypt an encrypted message
	 *
	 * @param string $ciphertext
	 * @param string $recipientPublicKey
	 * @return array|null
     * @throws \ReflectionException
	 */
	public static function decryptMessage ( $ciphertext, $recipientPublicKey )
	{
        static::includedSodium();

		list( $message, $shared, $nonce ) = \explode( '+', $ciphertext, 3 );

		return ( \in_array( null, [ $message, $shared, $nonce ], true ) ) ?
			null :
			\json_decode(
				\sodium_crypto_box_open(
					\hex2bin( $message ),
					\hex2bin( $nonce ),
					\sodium_crypto_box_keypair_from_secretkey_and_publickey(
						\hex2bin( $shared ),
						\hex2bin( $recipientPublicKey )
					)
				),
				true
			);
	}

	/**
	 * Derives a private key for encrypting data with the given key
	 *
	 * @param string $key
	 * @return string
	 * @throws \Exception|\ReflectionException
	 */
	public static function generateEncPrivateKey ( $key )
	{
        static::includedSodium();

		return \bin2hex(
			\sodium_crypto_box_secretkey(
				SHA3::init( SHA3::SHAKE256 )
					->absorb( $key )
					->squeeze( SODIUM_CRYPTO_BOX_KEYPAIRBYTES )
			)
		);
	}

	/**
	 * Derives a public key for encrypting data for this wallet's consumption
	 *
	 * @param string $privateKey
	 * @return string
     * @throws \ReflectionException
	 */
	public static function generateEncPublicKey ( $privateKey )
	{
        static::includedSodium();

		return \bin2hex(
			\sodium_crypto_box_publickey_from_secretkey(
				\hex2bin( $privateKey )
			)
		);
	}

	/**
	 * Creates a shared key by combining this wallet's private key and another wallet's public key
	 *
	 * @param string $privateKey
	 * @param string $otherPublicKey
	 * @return string
     * @throws \ReflectionException
	 */
	public static function generateEncSharedKey ( $privateKey, $otherPublicKey )
	{
	    static::includedSodium();

		return \bin2hex(
			\sodium_crypto_box_keypair_from_secretkey_and_publickey(
				\hex2bin( $privateKey ),
				\hex2bin( $otherPublicKey )
			)
		);
	}

    /**
     * @throws \ReflectionException
     */
    private static function includedSodium ()
    {
        if ( ! static::$sodium ) {
            Sodium::libsodium2sodium();
            static::$sodium = true;
        }
    }
}
