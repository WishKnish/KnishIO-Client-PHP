<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use desktopd\SHA3\Sponge as SHA3;
use BI\BigInteger;
use Exception;
use ReflectionException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Decimal;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\Libraries\Base58;

/**
 * Class Wallet
 * @package WishKnish\KnishIO\Client
 *
 * @property string $position
 * @property string $token
 * @property string|null $key
 * @property string|null $address
 * @property int|float $balance
 * @property array $molecules
 * @property string|null $bundle
 * @property string|null $privkey
 * @property string|null $pubkey
 * @property string|null $batchId
 * @property string|null $characters
 *
 */
class Wallet
{
    /**
     * @var string|null
     */
    public $batchId;

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
    public $address;

    /**
     * @var string|null
     */
    public $bundle;

    /**
     * @var string|null
     */
    public $key;

    /**
     * @var string|null
     */
    public $pubkey;

    /**
     * @var string|null
     */
    private $privkey;


    /**
     * @param string $secretOrBundle
     * @param string $token
     * @param string|null $batchId
     * @param string|null $characters
     * @return Wallet|WalletShadow
     * @throws Exception
     */
    public static function create ($secretOrBundle, $token, $batchId = null, $characters = null) {

    	// Shadow wallet
    	if (static::isBundleHash($secretOrBundle) ) {
			return new WalletShadow($secretOrBundle, $token, $batchId, $characters);
		}

    	// Base wallet
		$wallet = new Wallet($secretOrBundle, $token);
		$wallet->batchId = $batchId;
		$wallet->characters = defined(Base58::class . '::' . $characters ) ? $characters : null;
		return $wallet;
	}




	/**
	 * Wallet constructor.
	 *
	 * @param string $secret
	 * @param string $token
	 * @param string|null $position
	 * @param integer $saltLength
     * @param string $characters
	 * @throws Exception
	 */
	public function __construct ( $secret = null, $token = 'USER', $position = null, $saltLength = 64, $characters = null )
	{

		$this->position = $position ?: Strings::randomString( $saltLength );
		$this->token = $token;
        $this->characters = defined(Base58::class . '::' . $characters ) ? $characters : null;

		if ( $secret ) {

			$this->sign( $secret );

		}

	}

    /**
     * @param string $secret
     * @throws Exception
     */
	public function sign ( $secret )
    {

        if ( $this->key === null && $this->address === null && $this->bundle === null ) {

            $this->key = static::generateWalletKey( $secret, $this->token, $this->position );
            $this->address = static::generateWalletAddress( $this->key );
            $this->bundle = Crypto::generateBundleHash( $secret );
            $this->getMyEncPrivateKey();
            $this->getMyEncPublicKey();

        }

    }

    /**
     * @return string
     */
    public static function generateBatchId ()
    {
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
	 * @param string $key
	 * @return string
	 * @throws Exception
	 */
	protected static function generateWalletAddress ( $key )
	{

		$digestSponge = SHA3::init( SHA3::SHAKE256 );

		foreach ( Strings::chunkSubstr( $key, 128 ) as $idx => $fragment ) {

			$workingFragment = $fragment;

			foreach ( range( 1, 16 ) as $_ ) {

				$workingFragment = bin2hex(
					SHA3::init( SHA3::SHAKE256 )
						->absorb( $workingFragment )
						->squeeze( 64 )
				);

			}

			$digestSponge->absorb( $workingFragment );

		}

		return bin2hex(
			SHA3::init( SHA3::SHAKE256 )
				->absorb( bin2hex( $digestSponge->squeeze( 1024 ) ) )
				->squeeze( 32 )
		);

	}


	/**
	 * Get a recipient batch ID
	 * $this is a client sender wallet
	 *
	 * @param Wallet $senderWallet
	 * @param int|float $transferAmount
	 */
	public function initBatchId ($senderWallet, $transferAmount) {

		if ( $senderWallet->batchId ) {

			// Has a remainder value (source balance is bigger than a transfer value)
			if ( !$this->batchId && Decimal::cmp( $senderWallet->balance, $transferAmount ) > 0 ) {
				$batchId = static::generateBatchId();
			}

			// Has no remainder? use batch ID from the source wallet
			else {
				$batchId = $senderWallet->batchId;
			}

			// Set batchID to recipient wallet
			$this->batchId = $batchId;
		}

	}


	/**
	 * Derives a private key for encrypting data with this wallet's key
	 *
	 * @return string|null
	 * @throws Exception
	 */
	public function getMyEncPrivateKey ()
	{

        Crypto::setCharacters( $this->characters );

        if ( $this->privkey === null && $this->key !== null ) {

            $this->privkey = Crypto::generateEncPrivateKey( $this->key );

        }

		return $this->privkey;

	}

	/**
	 * Dervies a public key for encrypting data for this wallet's consumption
	 *
	 * @return string|null
	 * @throws Exception
	 */
	public function getMyEncPublicKey ()
	{

        Crypto::setCharacters( $this->characters );

        $privateKey = $this->getMyEncPrivateKey();

	    if ( $this->pubkey === null && $privateKey !== null ) {

            $this->pubkey = Crypto::generateEncPublicKey( $privateKey );

        }

		return $this->pubkey;

	}

    /**
     * @param array $message
     * @param mixed ...$keys
     * @return array
     * @throws ReflectionException|Exception
     */
    public function encryptMyMessage ( array $message, ...$keys )
    {

        Crypto::setCharacters( $this->characters );

        $encrypt = [];

        foreach ( $keys as $key ) {

            $encrypt[ Crypto::hashShare( $key ) ] = Crypto::encryptMessage( $message, $key );

        }

        return $encrypt;

    }

	/**
	 * Uses the current wallet's private key to decrypt the given message
	 *
	 * @param string|array $message
     *
	 * @return array|null
	 * @throws Exception
	 */
	public function decryptMyMessage ( $message )
	{

        Crypto::setCharacters( $this->characters );

        $pubKey = $this->getMyEncPublicKey();
        $encrypt = $message;

        if ( is_array( $message ) ) {

            $hash = Crypto::hashShare( $pubKey );
            $encrypt = '0';

            if ( array_key_exists( $hash,  $message ) ) {

                $encrypt = $message[ $hash ];

            }

        }

        return Crypto::decryptMessage( $encrypt, $this->getMyEncPrivateKey(), $pubKey );

	}

	/**
	 * @param string $secret
	 * @param string $token
	 * @param string $position
	 * @return string
	 * @throws Exception
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
		return bin2hex(
			SHA3::init( SHA3::SHAKE256 )
				->absorb( bin2hex(
					$intermediateKeySponge
						->squeeze( 1024 )
				) )->squeeze( 1024 )
		);

	}

}
