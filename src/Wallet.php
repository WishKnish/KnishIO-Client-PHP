<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use BI\BigInteger;
use Exception;
use ReflectionException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
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
class Wallet {
  /**
   * @var string|null
   */
  public $batchId;

  /**
   * @var array
   */
  public $molecules = [];

  /**
   * @var array
   */
  public $tokenUnits = [];

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
  public $position;

  /**
   * @var string|null
   */
  public $bundle;

  /**
   * @var string
   */
  public $token;

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
   * Wallet constructor.
   *
   * @param null $secret
   * @param string $token
   * @param null $position
   * @param null $batchId
   * @param null $characters
   *
   * @throws Exception
   */
  public function __construct ( $secret = null, $token = 'USER', $position = null, $batchId = null, $characters = null ) {
    $this->token = $token;
    $this->bundle = $secret ? Crypto::generateBundleHash( $secret ) : null;
    $this->batchId = $batchId;
    $this->characters = defined( Base58::class . '::' . $characters ) ? $characters : null;
    $this->position = $position;

    if ( $secret ) {

      // Generate a position for non-shadow wallet if it does not initialized
      $this->position = $this->position ?? static::generateWalletPosition();

      $this->prepareKeys( $secret );

    }
  }

  /**
   * @param string $secretOrBundle
   * @param string $token
   * @param string|null $batchId
   * @param string|null $characters
   *
   * @return Wallet
   * @throws Exception
   */
  public static function create ( string $secretOrBundle, string $token = 'USER', ?string $batchId = null, ?string $characters = null ) {
    $secret = static::isBundleHash( $secretOrBundle ) ? null : $secretOrBundle;
    $bundle = $secret ? Crypto::generateBundleHash( $secret ) : $secretOrBundle;
    $position = $secret ? static::generateWalletPosition() : null;

    // Wallet initialization
    $wallet = new Wallet( $secret, $token, $position, $batchId, $characters );
    $wallet->bundle = $bundle;
    return $wallet;
  }

  /**
   * @param array $unitsData
   *
   * @return array
   */
  public static function getTokenUnits ( array $unitsData ): array {
    $result = [];

    foreach ( $unitsData as $unitData ) {

      // !!! @todo supporting wrong token creation with simple array: need to be deleted after db clearing
      if ( !is_array($unitData) ) {
        $items = [
            'id' => $unitData,
            'name' => null,
            'metas' => [],
        ];
      }

      // Standart token unit format
      else {
        $result[] = [
            'id' => array_shift( $unitData ),
            'name' => array_shift( $unitData ),
            'metas' => $unitData,
        ];
      }
    }
    return $result;
  }

  /**
   * @return bool
   */
  public function isShadow (): bool {
    return !$this->position && !$this->address;
  }

  /**
   * @return bool
   */
  public function hasTokenUnits (): bool {
    return property_exists( $this, 'tokenUnits' ) && count( $this->tokenUnits ) > 0;
  }

  /**
   * @return string
   */
  public function tokenUnitsJson (): ?string {

    if ( $this->hasTokenUnits() ) {

      $result = array_map(
        static function( $tokenUnit, $_ ) {
          return array_merge( [ $tokenUnit[ 'id' ], $tokenUnit[ 'name' ] ], $tokenUnit[ 'metas' ] );
        },
        $this->tokenUnits,
        []
      );

      return json_encode( $result );
    }

    return null;
  }

  /**
   * @param array $units
   * @param Wallet $remainderWallet
   * @param Wallet|null $recipientWallet
   */
  public function splitUnits ( array $sendTokenUnits = [], Wallet $remainderWallet, ?Wallet $recipientWallet = null ) {

    // No units supplied, nothing to split
    if ( count( $sendTokenUnits ) === 0 ) {
      return;
    }

    // Init recipient & remainder token units
    $recipientTokenUnits = [];
    $remainderTokenUnits = [];

    // Init recipient & remainder token units
    $recipientTokenUnits = []; $remainderTokenUnits = [];
    foreach( $this->tokenUnits as $tokenUnit ) {
      if ( in_array( $tokenUnit[ 'id' ], $sendTokenUnits ) ) {
        $recipientTokenUnits[] = $tokenUnit;
      }
      else {
        $remainderTokenUnits[] = $tokenUnit;
      }
    }

    /*
    array_walk( $this->tokenUnits, static function ( $tokenUnit ) use ( $units, $recipientTokenUnits, $remainderTokenUnits ) {
      array_push(
        in_array( $tokenUnit[ 'id' ], $units, true ) ? $recipientTokenUnits : $remainderTokenUnits,
        $tokenUnit
      );
    } );
    */

    // Reset token units to the sending value
    $this->tokenUnits = $recipientTokenUnits;

    // Set token units to recipient & remainder
    if ( $recipientWallet !== null ) {
      $recipientWallet->tokenUnits = $recipientTokenUnits;
    }

    $remainderWallet->tokenUnits = $remainderTokenUnits;
  }

  /**
   * @param string $secret
   *
   * @throws Exception
   */
  public function prepareKeys ( string $secret ) {
    if ( $this->key === null && $this->address === null ) {

      $this->key = static::generateWalletKey( $secret, $this->token, $this->position );
      $this->address = static::generateWalletAddress( $this->key );
      $this->getMyEncPrivateKey();
      $this->getMyEncPublicKey();

    }
  }


  /**
   * @param mixed $code
   *
   * @return bool
   */
  public static function isBundleHash ( $code ): bool {

    return is_string( $code ) && mb_strlen( $code ) === 64 && ctype_xdigit( $code );
  }

  /**
   * @param int $saltLength
   *
   * @return string
   */
  protected static function generateWalletPosition ( $saltLength = 64 ): string {
    return Strings::randomString( $saltLength );
  }

  /**
   * @param string $key
   *
   * @return string
   * @throws Exception
   */
  protected static function generateWalletAddress ( $key ): string {

    $digestSponge = Crypto\Shake256::init();

    foreach ( Strings::chunkSubstr( $key, 128 ) as $idx => $fragment ) {

      $workingFragment = $fragment;

      foreach ( range( 1, 16 ) as $_ ) {

        $workingFragment = bin2hex( Crypto\Shake256::hash( $workingFragment, 64 ) );

      }

      $digestSponge->absorb( $workingFragment );

    }

    return bin2hex( Crypto\Shake256::hash( bin2hex( $digestSponge->squeeze( 1024 ) ), 32 ) );

  }

  /**
   * Get a recipient batch ID
   *
   * @param $senderWallet
   * @param bool $remainder
   *
   * @throws Exception
   */
  public function initBatchId ( $senderWallet, bool $remainder = false ) {

    if ( $senderWallet->batchId ) {
      $this->batchId = $remainder ? $senderWallet->batchId : Crypto::generateBatchId();
    }

  }

  /**
   * Derives a private key for encrypting data with this wallet's key
   *
   * @return string|null
   * @throws Exception
   */
  public function getMyEncPrivateKey () {

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
  public function getMyEncPublicKey () {

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
   *
   * @return array
   * @throws ReflectionException|Exception
   */
  public function encryptMyMessage ( array $message, ...$keys ) {

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
  public function decryptMyMessage ( $message ) {

    Crypto::setCharacters( $this->characters );

    $pubKey = $this->getMyEncPublicKey();
    $encrypt = $message;

    if ( is_array( $message ) ) {

      $hash = Crypto::hashShare( $pubKey );
      $encrypt = '0';

      if ( array_key_exists( $hash, $message ) ) {

        $encrypt = $message[ $hash ];

      }

    }

    return Crypto::decryptMessage( $encrypt, $this->getMyEncPrivateKey(), $pubKey );

  }

  /**
   * @param string $secret
   * @param string $token
   * @param string $position
   *
   * @return string
   * @throws Exception
   */
  public static function generateWalletKey ( $secret, $token, $position ) {

    // Converting secret to bigInt
    $bigIntSecret = new BigInteger( $secret, 16 );

    // Adding new position to the user secret to produce the indexed key
    $indexedKey = $bigIntSecret->add( new BigInteger( $position, 16 ) );

    // Hashing the indexed key to produce the intermediate key
    $intermediateKeySponge = Crypto\Shake256::init()
      ->absorb( $indexedKey->toString( 16 ) );

    if ( $token !== '' ) {

      $intermediateKeySponge->absorb( $token );

    }

    // Hashing the intermediate key to produce the private key
    return bin2hex( Crypto\Shake256::hash( bin2hex( $intermediateKeySponge->squeeze( 1024 ) ), 1024 ) );

  }

}
