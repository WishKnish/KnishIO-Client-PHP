<?php
/*
                               (
                              (/(
                              (//(
                              (///(
                             (/////(
                             (//////(                          )
                            (////////(                        (/)
                            (////////(                       (///)
                           (//////////(                      (////)
                           (//////////(                     (//////)
                          (////////////(                    (///////)
                         (/////////////(                   (/////////)
                        (//////////////(                  (///////////)
                        (///////////////(                (/////////////)
                       (////////////////(               (//////////////)
                      (((((((((((((((((((              (((((((((((((((
                     (((((((((((((((((((              ((((((((((((((
                     (((((((((((((((((((            ((((((((((((((
                    ((((((((((((((((((((           (((((((((((((
                    ((((((((((((((((((((          ((((((((((((
                    (((((((((((((((((((         ((((((((((((
                    (((((((((((((((((((        ((((((((((
                    ((((((((((((((((((/      (((((((((
                    ((((((((((((((((((     ((((((((
                    (((((((((((((((((    (((((((
                   ((((((((((((((((((  (((((
                   #################  ##
                   ################  #
                  ################# ##
                 %################  ###
                 ###############(   ####
                ###############      ####
               ###############       ######
              %#############(        (#######
             %#############           #########
            ############(              ##########
           ###########                  #############
          #########                      ##############
        %######

        Powered by Knish.IO: Connecting a Decentralized World

Please visit https://github.com/WishKnish/KnishIO-Client-PHP for information.

License: https://github.com/WishKnish/KnishIO-Client-PHP/blob/master/LICENSE
 */

namespace WishKnish\KnishIO\Client;

use BI\BigInteger;
use Exception;
use JsonException;
use ReflectionException;
use SodiumException;
use WishKnish\KnishIO\Client\Exception\CodeException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Strings;

/**
 * Class Wallet
 * @package WishKnish\KnishIO\Client
 *
 * @property string $position
 * @property string $token
 * @property string|null $key
 * @property string|null $address
 * @property float $balance
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
  public ?string $batchId = null;

  /**
   * @var array
   */
  public array $molecules = [];

  /**
   * @var array
   */
  public array $tokenUnits = [];

  /**
   * @var float
   */
  public float $balance = 0;

  /**
   * @var string|null
   */
  public ?string $address = null;

  /**
   * @var string|null
   */
  public ?string $position = null;

  /**
   * @var string|null
   */
  public ?string $bundle = null;

  /**
   * @var string
   */
  public string $token;

  /**
   * @var string|null
   */
  public ?string $key = null;

  /**
   * @var string|null
   */
  public ?string $pubkey = null;

  /**
   * @var string|null
   */
  private ?string $privkey = null;

  /**
   * Wallet constructor.
   *
   * @param string|null $secret
   * @param string $token
   * @param string|null $position
   * @param string|null $batchId
   * @param string|null $characters
   *
   * @throws Exception
   */
  public function __construct ( string $secret = null, string $token = 'USER', string $position = null, string $batchId = null, string $characters = null ) {
    $this->token = $token;
    $this->bundle = $secret ? Crypto::generateBundleHash( $secret ) : null;
    $this->batchId = $batchId;
    $this->characters = $characters ?? 'BASE64';
    $this->position = $position;

    if ( $secret ) {

      // Generate a position for non-shadow wallet if not initialized
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
  public static function create ( string $secretOrBundle, string $token = 'USER', ?string $batchId = null, ?string $characters = null ): Wallet {
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
      if ( !is_array( $unitData ) ) {
        $result[] = [ 'id' => $unitData, 'name' => null, 'metas' => [], ];
      }

      // Standard token unit format
      else {
        $result[] = [ 'id' => array_shift( $unitData ), 'name' => array_shift( $unitData ), 'metas' => $unitData, ];
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
   * @param Wallet $sourceWallet
   * @param bool $isRemainder
   *
   * @throws Exception
   */
  public function initBatchId ( Wallet $sourceWallet, bool $isRemainder = false ): void {
    if ( $sourceWallet->batchId ) {
      $this->batchId = $isRemainder ? $sourceWallet->batchId : Crypto::generateBatchId();
    }
  }

  /**
   * @return bool
   */
  public function hasTokenUnits (): bool {
    return property_exists( $this, 'tokenUnits' ) && count( $this->tokenUnits ) > 0;
  }

  /**
   * @return string|null
   * @throws JsonException
   */
  public function tokenUnitsJson (): ?string {

    if ( $this->hasTokenUnits() ) {

      $result = array_map( static function ( $tokenUnit ) {
        return array_merge( [ $tokenUnit[ 'id' ], $tokenUnit[ 'name' ] ], $tokenUnit[ 'metas' ] );
      }, $this->tokenUnits, [] );

      return json_encode( $result, JSON_THROW_ON_ERROR );
    }

    return null;
  }

  /**
   * @param array $sendTokenUnits
   * @param Wallet $remainderWallet
   * @param Wallet|null $recipientWallet
   */
  public function splitUnits ( array $sendTokenUnits, Wallet $remainderWallet, ?Wallet $recipientWallet = null ): void {

    // No units supplied, nothing to split
    if ( count( $sendTokenUnits ) === 0 ) {
      return;
    }

    // Init recipient & remainder token units
    $recipientTokenUnits = [];
    $remainderTokenUnits = [];
    foreach ( $this->tokenUnits as $tokenUnit ) {
      if ( in_array( $tokenUnit[ 'id' ], $sendTokenUnits, true ) ) {
        $recipientTokenUnits[] = $tokenUnit;
      }
      else {
        $remainderTokenUnits[] = $tokenUnit;
      }
    }

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
  public function prepareKeys ( string $secret ): void {
    if ( $this->key === null && $this->address === null ) {

      $this->key = static::generateWalletKey( $secret, $this->token, $this->position );
      $this->address = static::generateWalletAddress( $this->key );
      $this->getMyEncPrivateKey();
      $this->getMyEncPublicKey();

    }
  }

  /**
   * @param string $code
   *
   * @return bool
   */
  public static function isBundleHash ( string $code ): bool {
    return mb_strlen( $code ) === 64 && ctype_xdigit( $code );
  }

  /**
   * @param int $saltLength
   *
   * @return string
   * @throws Exception
   */
  protected static function generateWalletPosition ( int $saltLength = 64 ): string {
    return Strings::randomString( $saltLength );
  }

  /**
   * @param string $key
   *
   * @return string
   * @throws Exception
   */
  protected static function generateWalletAddress ( string $key ): string {

    $digestSponge = Crypto\Shake256::init();

    foreach ( Strings::chunkSubstr( $key, 128 ) as $workingFragment ) {
      foreach ( range( 1, 16 ) as $ignored ) {
        $workingFragment = bin2hex( Crypto\Shake256::hash( $workingFragment, 64 ) );
      }

      $digestSponge->absorb( $workingFragment );
    }

    return bin2hex( Crypto\Shake256::hash( bin2hex( $digestSponge->squeeze( 1024 ) ), 32 ) );
  }

  /**
   * Derives a private key for encrypting data with this wallet's key
   *
   * @return string|null
   * @throws Exception
   */
  public function getMyEncPrivateKey (): ?string {

    if ( $this->characters ) {
      Crypto::setCharacters( $this->characters );
    }

    if ( $this->privkey === null && $this->key !== null ) {
      $this->privkey = Crypto::generateEncPrivateKey( $this->key );
    }

    return $this->privkey;
  }

  /**
   * Derives a public key for encrypting data for this wallet's consumption
   *
   * @return string|null
   * @throws Exception
   */
  public function getMyEncPublicKey (): ?string {

    if ( $this->characters ) {
      Crypto::setCharacters( $this->characters );
    }

    $privateKey = $this->getMyEncPrivateKey();

    if ( $this->pubkey === null && $privateKey !== null ) {
      $this->pubkey = Crypto::generateEncPublicKey( $privateKey );
    }

    return $this->pubkey;
  }

  /**
   * @param string $message
   * @param ...$pubkeys
   *
   * @return array
   * @throws ReflectionException
   */
  public function encryptBinary ( string $message, ...$pubkeys ): array {
    return $this->encryptMyMessage( base64_encode( $message ), ...$pubkeys );
  }

  /**
   * @param array|string $message
   *
   * @return mixed
   * @throws JsonException
   * @throws ReflectionException
   * @throws SodiumException
   */
  public function decryptBinary ( array|string $message ): mixed {
    $decrypt = $this->decryptMyMessage( $message );

    if ( $decrypt !== null ) {
      $decrypt = base64_decode( $decrypt, true );

      if ( $decrypt === false ) {
        $decrypt = null;
      }
    }

    return $decrypt;
  }

  /**
   * @param mixed $message
   * @param mixed ...$pubkeys
   *
   * @return array
   * @throws JsonException
   * @throws ReflectionException
   * @throws SodiumException
   */
  public function encryptMyMessage ( mixed $message, ...$pubkeys ): array {

    if ( $this->characters ) {
      Crypto::setCharacters( $this->characters );
    }

    $encrypt = [];

    foreach ( $pubkeys as $pubkey ) {
      $encrypt[ Crypto::hashShare( $pubkey ) ] = Crypto::encryptMessage( $message, $pubkey );
    }

    return $encrypt;
  }

  /**
   * Uses the current wallet's private key to decrypt the given message
   *
   * @param array|string $message
   *
   * @return mixed
   * @throws JsonException
   * @throws ReflectionException
   * @throws SodiumException
   */
  public function decryptMyMessage ( array|string $message ): mixed {

    if ( $this->characters ) {
      Crypto::setCharacters( $this->characters );
    }

    $pubkey = $this->getMyEncPublicKey();

    $encrypted = $message;
    if ( is_array( $message ) ) {

      $hash = Crypto::hashShare( $pubkey );

      if ( !array_key_exists( $hash, $message ) ) {
        throw new CodeException( 'Wallet::decryptMyMessage - hash does not found for the wallet\'s pubkey.' );
      }

      $encrypted = $message[ $hash ];
    }

    return Crypto::decryptMessage( $encrypted, $this->getMyEncPrivateKey(), $pubkey );
  }

  /**
   * @param string $secret
   * @param string $token
   * @param string $position
   *
   * @return string
   * @throws Exception
   */
  public static function generateWalletKey ( string $secret, string $token, string $position ): string {

    // Converting secret to bigInt
    // Adding new position to the user secret to produce the indexed key
    $indexedKey = ( new BigInteger( $secret, 16 ) )->add( new BigInteger( $position, 16 ) );

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
