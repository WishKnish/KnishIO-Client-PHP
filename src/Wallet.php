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
use SodiumException;
use WishKnish\KnishIO\Client\Exception\CodeException;
use WishKnish\KnishIO\Client\Exception\CryptoException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\PostQuantumCrypto;
use WishKnish\KnishIO\Client\Libraries\Soda;
use WishKnish\KnishIO\Client\Libraries\Strings;

/**
 * Class Wallet
 * @package WishKnish\KnishIO\Client
 *
 * @property string $position
 * @property string $token
 * @property string|null $batchId
 * @property string|null $characters
 * @property string|null $key
 * @property string|null $address
 * @property int $balance
 * @property string|null $type
 * @property string|null $bundle
 * @property string|null $privkey
 * @property string|null $pubkey
 * @property string|null $createdAt
 * @property array $tokenUnits
 * @property array $tradeRates
 * @property Soda $soda
 */
class Wallet {

  /**
   * @var int
   */
  public int $balance = 0;

  /**
   * @var string|null
   */
  public ?string $address = null;

  /**
   * @var string|null
   */
  public ?string $bundle = null;

  /**
   * @var string
   */
  public string $type = 'regular';

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
   * @var string|null ML-KEM768 public key (Base64)
   */
  private ?string $mlkemPublicKey = null;

  /**
   * @var string|null ML-KEM768 private key (Base64)
   */
  private ?string $mlkemPrivateKey = null;

  /**
   * @var string|null
   */
  public ?string $createdAt = null;

  /**
   * @var string|null
   */
  public ?string $tokenName = null;

  /**
   * @var int
   */
  public int $tokenAmount = 0;

  /**
   * @var string|null
   */
  public ?string $tokenSupply = null;

  /**
   * @var string|null
   */
  public ?string $tokenFungibility = null;

  /**
   * @var array
   */
  public array $tokenUnits = [];

  /**
   * @var array
   */
  public array $tradeRates = [];

  /**
   * @var Soda|null
   */
  protected ?Soda $soda = null;

  /**
   * @param string|null $secret
   * @param string|null $token
   * @param string|null $position
   * @param string|null $batchId
   * @param string|null $characters
   *
   * @throws SodiumException
   */
  public function __construct (
    ?string $secret = null,
    public ?string $token = 'USER',
    public ?string $position = null,
    public ?string $batchId = null,
    public ?string $characters = null
  ) {
    if ( $secret ) {

      // Set bundle from the secret
      $this->bundle = Crypto::generateBundleHash( $secret );

      // Generate a position for non-shadow wallet if not initialized
      $this->position = $this->position ?? static::generatePosition();

      // Key & address initialization
      $this->key = static::generateKey( $secret, $this->token, $this->position );
      $this->address = static::generateAddress( $this->key );

      // Soda object initialization
      $this->soda = new Soda( $this->characters );

      // Private key initialization for classical crypto
      $this->privkey = $this->soda->generatePrivateKey( $this->key );
      
      // Initialize ML-KEM768 keys for quantum resistance
      $this->initializeMLKEM();

      // Set characters
      $this->characters = $this->characters ?? 'BASE64';
    }
  }

  /**
   * @param string $secretOrBundle
   * @param string $token
   * @param string|null $batchId
   * @param string|null $characters
   *
   * @return Wallet
   * @throws SodiumException
   */
  public static function create ( string $secretOrBundle, string $token = 'USER', ?string $batchId = null, ?string $characters = null ): Wallet {
    $secret = Crypto::isBundleHash( $secretOrBundle ) ? null : $secretOrBundle;
    $bundle = $secret ? Crypto::generateBundleHash( $secret ) : $secretOrBundle;
    $position = $secret ? static::generatePosition() : null;

    // Wallet initialization
    $wallet = new Wallet( $secret, $token, $position, $batchId, $characters );
    $wallet->bundle = $bundle;
    return $wallet;
  }

  /**
   * Initialize ML-KEM768 keys for quantum resistance
   * 
   * @return void
   * @throws Exception
   */
  private function initializeMLKEM(): void {
    if (!$this->key) {
      return;
    }
    
    // Generate a 64-byte (512-bit) seed from the Knish.IO private key
    // generateSecret returns hex, so we need 128 hex chars for 64 bytes
    $seedHex = Crypto::generateSecret($this->key, 128);
    
    // Generate ML-KEM768 key pair from seed
    $keyPair = PostQuantumCrypto::generateMLKEMKeyPairFromSeed($seedHex);
    
    // Store the ML-KEM keys
    $this->mlkemPublicKey = $keyPair['publicKey'];
    $this->mlkemPrivateKey = $keyPair['privateKey'];
    
    // Set pubkey to the ML-KEM768 public key (1580 chars) for quantum resistance
    $this->pubkey = $this->mlkemPublicKey;
  }

  /**
   * @param array $unitsData
   *
   * @return array
   */
  public static function getTokenUnits ( array $unitsData ): array {
    $result = [];
    foreach ( $unitsData as $unitData ) {
      $result[] = TokenUnit::createFromDB( $unitData );
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
   */
  public function initBatchId ( Wallet $sourceWallet, bool $isRemainder = false ): void {
    if ( $sourceWallet->batchId ) {
      $this->batchId = $isRemainder ? $sourceWallet->batchId : Crypto::generateBatchId();
    }
  }

  /**
   * @return array
   */
  public function getTokenUnitsData (): array {
    $result = [];
    foreach ( $this->tokenUnits as $tokenUnit ) {
      $result[] = $tokenUnit->toData();
    }
    return $result;
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
      if ( in_array( $tokenUnit->id, $sendTokenUnits, true ) ) {
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
   * @return Wallet
   * @throws SodiumException
   */
  public function createRemainder( string $secret ): self {
    $remainderWallet = self::create( $secret, $this->token, $this->batchId, $this->characters );
    $remainderWallet->initBatchId( $this, true );
    return $remainderWallet;
  }

  /**
   * @param string $message
   * @param ...$pubkeys
   *
   * @return array
   * @throws JsonException
   * @throws SodiumException
   */
  public function encryptBinary ( string $message, ...$pubkeys ): array {
    return $this->encryptMessage( base64_encode( $message ), ...$pubkeys );
  }

  /**
   * @param array|string $message
   *
   * @return mixed
   * @throws JsonException
   * @throws SodiumException
   */
  public function decryptBinary ( array|string $message ): mixed {
    $decrypt = $this->decryptMessage( $message );

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
   * @throws SodiumException
   */
  public function encryptMessage ( mixed $message, ...$pubkeys ): array {

    if ( !$this->soda ) {
      throw new CryptoException( 'To use encryption, the wallet must be initialized with a secret argument.' );
    }

    $encrypt = [];
    foreach ( $pubkeys as $pubkey ) {
      // $pubkey = $pubkey instanceof self ? $pubkey->pubkey : $pubkey; // Can use a list of wallets
      $encrypt[ $this->soda->shortHash( $pubkey ) ] = $this->soda->encrypt( $message, $pubkey );
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
   * @throws SodiumException
   */
  public function decryptMessage ( array|string $message ): mixed {

    if ( !$this->soda ) {
      throw new CryptoException( 'To use encryption, the wallet must be initialized with a secret argument.' );
    }

    $encrypted = $message;
    if ( is_array( $message ) ) {

      $hash = $this->soda->shortHash( $this->pubkey );

      if ( !array_key_exists( $hash, $message ) ) {
        throw new CodeException( 'Wallet::decryptMessage - hash does not found for the wallet\'s pubkey.' );
      }

      $encrypted = $message[ $hash ];
    }

    return $this->soda->decrypt( $encrypted, $this->privkey, $this->pubkey );
  }

  /**
   * @param int $saltLength
   *
   * @return string
   */
  protected static function generatePosition ( int $saltLength = 64 ): string {
    return Strings::randomString( $saltLength );
  }

  /**
   * @param string $key
   *
   * @return string
   */
  protected static function generateAddress ( string $key ): string {

    $digestSponge = Crypto\Shake256::init();

    foreach ( Strings::chunkSubstr( $key, 128 ) as $workingFragment ) {
      foreach ( range( 1, 16 ) as $ignored ) {
        $workingFragment = bin2hex( Crypto\Shake256::hash( $workingFragment, 64 ) );
      }

      try {
        $digestSponge->absorb( $workingFragment );
      }
      catch ( Exception $e ) {
        throw new CryptoException( $e->getMessage(), $e->getCode(), $e );
      }
    }

    try {
      return bin2hex( Crypto\Shake256::hash( bin2hex( $digestSponge->squeeze( 1024 ) ), 32 ) );
    }
    catch ( Exception $e ) {
      throw new CryptoException( $e->getMessage(), $e->getCode(), $e );
    }
  }

  /**
   * @param string $secret
   * @param string $token
   * @param string $position
   *
   * @return string
   */
  public static function generateKey ( string $secret, string $token, string $position ): string {

    // Converting secret to bigInt
    // Adding new position to the user secret to produce the indexed key
    $indexedKey = ( new BigInteger( $secret, 16 ) )->add( new BigInteger( $position, 16 ) );

    try {
      // Hashing the indexed key to produce the intermediate key
      $intermediateKeySponge = Crypto\Shake256::init()
        ->absorb( $indexedKey->toString( 16 ) );

      if ( $token !== '' ) {
        $intermediateKeySponge->absorb( $token );
      }

      // Hashing the intermediate key to produce the private key
      return bin2hex( Crypto\Shake256::hash( bin2hex( $intermediateKeySponge->squeeze( 1024 ) ), 1024 ) );
    }
    catch ( Exception $e ) {
      throw new CryptoException( $e->getMessage(), $e->getCode(), $e );
    }
  }

  /**
   * @param int $amount
   *
   * @return bool
   */
  public function hasEnoughBalance ( int $amount ): bool {
    return $this->balance >= $amount;
  }

}
