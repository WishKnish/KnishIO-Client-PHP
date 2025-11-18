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
   * Matches JavaScript implementation exactly
   * 
   * @return void
   * @throws Exception
   */
  private function initializeMLKEM(): void {
    if (!$this->key) {
      return;
    }
    
    // Generate a 64-byte (512-bit) seed from the Knish.IO private key  
    // Use deterministic approach matching JavaScript: generateSecret(key, 128) → 128 hex chars = 64 bytes
    $seedHex = Crypto::generateSecret($this->key, 128);  // 128 hex chars = 64 bytes
    
    // Generate real ML-KEM768 key pair using OpenSSL (deterministic from seed)
    $keyPair = PostQuantumCrypto::generateMLKEMKeyPairFromSeed($seedHex);
    
    // Store the ML-KEM keys
    $this->mlkemPublicKey = $keyPair['publicKey'];
    $this->mlkemPrivateKey = $keyPair['privateKey'];
    
    // Set pubkey to the ML-KEM768 public key for quantum resistance
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

  // =============================================================================
  // ML-KEM768 POST-QUANTUM ENCRYPTION (JavaScript SDK Compatibility)
  // =============================================================================

  /**
   * Encrypt a message using ML-KEM768 post-quantum encryption
   * Compatible with JavaScript SDK encryptMessage() method
   *
   * @param mixed $message Message to encrypt
   * @param string $recipientPubkey Recipient's ML-KEM768 public key (base64)
   * @return array{cipherText: string, encryptedMessage: string}
   * @throws JsonException|Exception
   */
  public function encryptMessageML768(mixed $message, string $recipientPubkey): array {
    try {
      // Serialize message to JSON string (matches JavaScript)
      $messageString = json_encode($message, JSON_THROW_ON_ERROR);
      $messageBytes = $messageString;

      // Perform ML-KEM768 encapsulation using real PostQuantumCrypto (Noble bridge)
      $encapsulateResult = PostQuantumCrypto::encapsulate($recipientPubkey);
      $sharedSecretBase64 = $encapsulateResult['sharedSecret'];
      $cipherTextBase64 = $encapsulateResult['ciphertext'];

      // Decode shared secret for AES-GCM encryption
      $sharedSecret = base64_decode($sharedSecretBase64);

      // Encrypt message using AES-GCM with shared secret (matches JavaScript WebCrypto API)
      $iv = random_bytes(12); // 96-bit IV for AES-GCM
      $encryptedContent = $this->encryptWithAESGCM($messageBytes, $sharedSecret, $iv);
      $encryptedMessage = $iv . $encryptedContent; // Prepend IV like JavaScript

      return [
        'cipherText' => $cipherTextBase64,  // Already base64 encoded
        'encryptedMessage' => base64_encode($encryptedMessage)
      ];
    } catch (Exception $e) {
      throw new CryptoException('ML-KEM768 encryption failed: ' . $e->getMessage());
    }
  }

  /**
   * Decrypt a message using ML-KEM768 post-quantum decryption  
   * Compatible with JavaScript SDK decryptMessage() method
   *
   * @param array{cipherText: string, encryptedMessage: string} $encryptedData
   * @return mixed Decrypted message
   * @throws JsonException|Exception
   */
  public function decryptMessageML768(array $encryptedData): mixed {
    try {
      // Decode encryptedMessage (IV + encrypted content)
      $encryptedMessage = base64_decode($encryptedData['encryptedMessage']);

      // Use this wallet's ML-KEM768 private key for decapsulation
      if (!$this->mlkemPrivateKey) {
        throw new Exception('ML-KEM768 private key not available');
      }

      // Perform ML-KEM768 decapsulation using real PostQuantumCrypto (Noble bridge)
      // cipherText is already base64 encoded in the encrypted data
      $sharedSecretBase64 = PostQuantumCrypto::decapsulate($encryptedData['cipherText'], $this->mlkemPrivateKey);
      $sharedSecret = base64_decode($sharedSecretBase64);

      // Extract IV and encrypted content (matches JavaScript)
      if (strlen($encryptedMessage) < 12) {
        throw new Exception('Invalid encrypted message format');
      }

      $iv = substr($encryptedMessage, 0, 12);
      $encryptedContent = substr($encryptedMessage, 12);

      // Decrypt using real AES-GCM with shared secret (matches JavaScript)
      $decryptedBytes = $this->decryptWithAESGCM($encryptedContent, $sharedSecret, $iv);

      // Convert back to JSON (matches JavaScript)
      return json_decode($decryptedBytes, true, 512, JSON_THROW_ON_ERROR);

    } catch (Exception $e) {
      error_log('Wallet::decryptMessageML768() - Decryption failed: ' . $e->getMessage());
      return null;
    }
  }

  /**
   * Encrypt data using AES-GCM with shared secret (matches JavaScript WebCrypto API)
   */
  private function encryptWithAESGCM(string $message, string $sharedSecret, string $iv): string {
    // Use real AES-256-GCM encryption to match JavaScript
    $tag = null;
    $encrypted = openssl_encrypt(
      $message,
      'aes-256-gcm',
      $sharedSecret,
      OPENSSL_RAW_DATA,
      $iv,
      $tag
    );
    
    if ($encrypted === false) {
      throw new Exception('AES-GCM encryption failed');
    }
    
    // Append authentication tag (matches JavaScript GCM behavior)
    return $encrypted . $tag;
  }
  
  /**
   * Decrypt data using AES-GCM with shared secret (matches JavaScript WebCrypto API)
   */
  private function decryptWithAESGCM(string $ciphertext, string $sharedSecret, string $iv): string {
    // Extract authentication tag (last 16 bytes for GCM)
    if (strlen($ciphertext) < 16) {
      throw new Exception('Invalid ciphertext: too short for GCM tag');
    }
    
    $encryptedData = substr($ciphertext, 0, -16);
    $tag = substr($ciphertext, -16);
    
    // Use real AES-256-GCM decryption to match JavaScript
    $decrypted = openssl_decrypt(
      $encryptedData,
      'aes-256-gcm', 
      $sharedSecret,
      OPENSSL_RAW_DATA,
      $iv,
      $tag
    );
    
    if ($decrypted === false) {
      throw new Exception('AES-GCM decryption failed');
    }
    
    return $decrypted;
  }
  
  /**
   * Legacy method for backward compatibility (now redirects to AES-GCM)
   */
  private function encryptWithSharedSecret(string $message, string $sharedSecret, string $iv): string {
    return $this->encryptWithAESGCM($message, $sharedSecret, $iv);
  }

  /**
   * Legacy method for backward compatibility (now redirects to AES-GCM)
   */
  private function decryptWithSharedSecret(string $ciphertext, string $sharedSecret, string $iv): string {
    return $this->decryptWithAESGCM($ciphertext, $sharedSecret, $iv);
  }

  /**
   * Encrypt message for multiple recipients using ML-KEM768
   * Returns array with recipient pubkey hash as key
   *
   * @param mixed $message Message to encrypt
   * @param string ...$pubkeys Recipient public keys
   * @return array Encrypted messages keyed by pubkey hash
   * @throws Exception
   */
  public function encryptMessageML768Multi(mixed $message, string ...$pubkeys): array {
    if (!$this->soda) {
      throw new CryptoException('To use encryption, the wallet must be initialized with a secret argument.');
    }

    $encrypt = [];
    foreach ($pubkeys as $pubkey) {
      $hash = $this->soda->shortHash($pubkey);
      $encrypt[$hash] = $this->encryptMessageML768($message, $pubkey);
    }

    return $encrypt;
  }

  /**
   * Decrypt message from multi-recipient encrypted data
   * Automatically finds this wallet's encrypted message by pubkey hash
   *
   * @param array $multiEncryptedData Multi-recipient encrypted data from encryptMessageML768Multi()
   * @return mixed Decrypted message
   * @throws Exception
   */
  public function decryptMessageML768Multi(array $multiEncryptedData): mixed {
    if (!$this->soda) {
      throw new CryptoException('To use encryption, the wallet must be initialized with a secret argument.');
    }

    // Calculate hash of our own pubkey
    $ourHash = $this->soda->shortHash($this->pubkey);

    // Find our encrypted message
    if (!isset($multiEncryptedData[$ourHash])) {
      throw new CryptoException('No encrypted message found for this wallet');
    }

    // Decrypt our message
    return $this->decryptMessageML768($multiEncryptedData[$ourHash]);
  }

  /**
   * Encrypt binary data using ML-KEM768
   * Does not JSON encode - encrypts raw bytes directly
   *
   * @param string $binaryData Binary data to encrypt
   * @param string $recipientPubkey Recipient's ML-KEM768 public key
   * @return array Encrypted data structure
   * @throws Exception
   */
  public function encryptBinaryML768(string $binaryData, string $recipientPubkey): array {
    try {
      // For binary data, use raw bytes without JSON encoding
      $messageBytes = $binaryData;

      // Perform ML-KEM768 encapsulation
      $encapsulateResult = PostQuantumCrypto::encapsulate($recipientPubkey);
      $sharedSecretBase64 = $encapsulateResult['sharedSecret'];
      $cipherTextBase64 = $encapsulateResult['ciphertext'];

      // Decode shared secret for AES-GCM encryption
      $sharedSecret = base64_decode($sharedSecretBase64);

      // Encrypt binary data using AES-GCM
      $iv = random_bytes(12); // 96-bit IV for AES-GCM
      $encryptedContent = $this->encryptWithAESGCM($messageBytes, $sharedSecret, $iv);
      $encryptedMessage = $iv . $encryptedContent; // Prepend IV

      return [
        'cipherText' => $cipherTextBase64,
        'encryptedMessage' => base64_encode($encryptedMessage),
        'isBinary' => true  // Flag to indicate binary data
      ];
    } catch (Exception $e) {
      throw new CryptoException('ML-KEM768 binary encryption failed: ' . $e->getMessage());
    }
  }

  /**
   * Decrypt binary data using ML-KEM768
   * Returns raw bytes without JSON decoding
   *
   * @param array $encryptedData Encrypted data structure
   * @return string Decrypted binary data
   * @throws Exception
   */
  public function decryptBinaryML768(array $encryptedData): string {
    try {
      // Decode encryptedMessage (IV + encrypted content)
      $encryptedMessage = base64_decode($encryptedData['encryptedMessage']);

      // Use this wallet's ML-KEM768 private key for decapsulation
      if (!$this->mlkemPrivateKey) {
        throw new Exception('ML-KEM768 private key not available');
      }

      // Perform ML-KEM768 decapsulation
      $sharedSecretBase64 = PostQuantumCrypto::decapsulate($encryptedData['cipherText'], $this->mlkemPrivateKey);
      $sharedSecret = base64_decode($sharedSecretBase64);

      // Extract IV and encrypted content
      if (strlen($encryptedMessage) < 12) {
        throw new Exception('Invalid encrypted message format');
      }

      $iv = substr($encryptedMessage, 0, 12);
      $encryptedContent = substr($encryptedMessage, 12);

      // Decrypt using AES-GCM and return raw bytes (no JSON decoding)
      return $this->decryptWithAESGCM($encryptedContent, $sharedSecret, $iv);

    } catch (Exception $e) {
      error_log('Wallet::decryptBinaryML768() - Decryption failed: ' . $e->getMessage());
      throw new CryptoException('ML-KEM768 binary decryption failed: ' . $e->getMessage());
    }
  }

}
