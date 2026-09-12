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

namespace WishKnish\KnishIO\Client\Storage;

/**
 * Class AesGcmSecretStorageProvider
 *
 * Standard OpenSSL AES-GCM envelope encryption secret storage provider.
 * Software provider: never hardware-backed.
 *
 * @package WishKnish\KnishIO\Client\Storage
 */
class AesGcmSecretStorageProvider implements SecretStorageProvider {

  public const KEY_PREFIX = 'knishio:secret:';

  /**
   * @var StorageBackend
   */
  protected StorageBackend $backend;

  /**
   * @var string|null
   */
  protected ?string $defaultPassphrase;

  /**
   * AesGcmSecretStorageProvider constructor.
   *
   * @param StorageBackend|null $backend
   * @param string|null $defaultPassphrase
   */
  public function __construct ( ?StorageBackend $backend = null, ?string $defaultPassphrase = null ) {
    $this->backend = $backend ?? new MemoryStorageBackend();
    $this->defaultPassphrase = $defaultPassphrase;
  }

  /**
   * @return string
   */
  public function getProviderType (): string {
    return 'aes-gcm';
  }

  /**
   * @return bool
   */
  public function isHardwareBacked (): bool {
    return false;
  }

  /**
   * @return bool
   */
  public function isAvailable (): bool {
    return function_exists( 'openssl_encrypt' ) && function_exists( 'openssl_decrypt' );
  }

  /**
   * @return StorageBackend
   */
  public function getBackend (): StorageBackend {
    return $this->backend;
  }

  /**
   * Store and encrypt a master secret for the given bundle hash
   *
   * @param string $bundleHash
   * @param string $secret
   * @param StorageOptions|null $options
   * @return void
   */
  public function storeSecret ( string $bundleHash, string $secret, ?StorageOptions $options = null ): void {
    if ( $bundleHash === '' ) {
      throw new SecretStorageException( 'Bundle hash cannot be empty' );
    }
    if ( $secret === '' ) {
      throw new SecretStorageException( 'Secret cannot be empty' );
    }

    $passphrase = $options?->passphrase ?? $this->defaultPassphrase;
    if ( $passphrase === null || $passphrase === '' ) {
      throw new SecretStorageException( 'Passphrase required for envelope encryption' );
    }

    $metadata = new SecretStorageMetadata(
      bundleHash: $bundleHash,
      label: $options?->label,
      createdAt: (int) round( microtime( true ) * 1000 ),
      hardwareBacked: false,
      providerType: $this->getProviderType()
    );

    $payload = SecretEnvelope::seal( $secret, $passphrase, $metadata );
    $this->backend->setItem( self::KEY_PREFIX . $bundleHash, $payload->toJson() );

    if ( $options?->recoveryPassphrase !== null && $options->recoveryPassphrase !== '' ) {
      $recoveryMetadata = new SecretStorageMetadata(
        bundleHash: $bundleHash,
        label: $options?->label,
        createdAt: (int) round( microtime( true ) * 1000 ),
        hardwareBacked: false,
        providerType: 'aes-gcm'
      );
      $recoveryPayload = SecretEnvelope::seal( $secret, $options->recoveryPassphrase, $recoveryMetadata );
      $this->backend->setItem( self::RECOVERY_KEY_PREFIX . $bundleHash, $recoveryPayload->toJson() );
    }
  }

  /**
   * Retrieve and decrypt the master secret for the given bundle hash
   *
   * @param string $bundleHash
   * @param StorageOptions|null $options
   * @return string|null
   */
  public function retrieveSecret ( string $bundleHash, ?StorageOptions $options = null ): ?string {
    $raw = $this->backend->getItem( self::KEY_PREFIX . $bundleHash );
    if ( $raw === null ) {
      return null;
    }

    $payload = EncryptedSecretPayload::fromJson( $raw );

    $passphrase = $options?->passphrase ?? $this->defaultPassphrase;
    if ( $passphrase === null || $passphrase === '' ) {
      throw new SecretStorageException( 'Passphrase required for secret decryption' );
    }

    return SecretEnvelope::open( $payload, $passphrase );
  }

  /**
   * Delete a stored secret
   *
   * @param string $bundleHash
   * @return bool
   */
  public function deleteSecret ( string $bundleHash ): bool {
    $result = $this->backend->removeItem( self::KEY_PREFIX . $bundleHash );
    $this->backend->removeItem( self::RECOVERY_KEY_PREFIX . $bundleHash );
    return $result;
  }

  /**
   * Check if a secret exists for the given bundle hash
   *
   * @param string $bundleHash
   * @return bool
   */
  public function hasSecret ( string $bundleHash ): bool {
    return $this->backend->getItem( self::KEY_PREFIX . $bundleHash ) !== null;
  }

  /**
   * List all stored secret metadata without exposing plaintext secrets
   *
   * @return array<int, SecretStorageMetadata>
   */
  public function listSecrets (): array {
    $results = [];
    foreach ( $this->backend->keys() as $key ) {
      if ( str_starts_with( $key, self::KEY_PREFIX ) && !str_starts_with( $key, self::RECOVERY_KEY_PREFIX ) ) {
        $raw = $this->backend->getItem( $key );
        if ( $raw !== null ) {
          try {
            $payload = EncryptedSecretPayload::fromJson( $raw );
            $results[] = $payload->metadata;
          } catch ( SecretStorageException ) {
            // Ignore corrupted envelopes in listSecrets
          }
        }
      }
    }
    return $results;
  }

  /**
   * Execute a callback with the unwrapped secret and zeroize memory upon completion
   *
   * @template T
   * @param string $bundleHash
   * @param callable(string): T $callback
   * @param StorageOptions|null $options
   * @return T
   */
  public function withSecret ( string $bundleHash, callable $callback, ?StorageOptions $options = null ): mixed {
    $raw = $this->backend->getItem( self::KEY_PREFIX . $bundleHash );
    if ( $raw === null ) {
      throw SecretStorageException::notFound( $bundleHash );
    }

    $payload = EncryptedSecretPayload::fromJson( $raw );

    $passphrase = $options?->passphrase ?? $this->defaultPassphrase;
    if ( $passphrase === null || $passphrase === '' ) {
      throw new SecretStorageException( 'Passphrase required for secret decryption' );
    }

    $secret = SecretEnvelope::open( $payload, $passphrase );

    try {
      return $callback( $secret );
    } finally {
      if ( function_exists( 'sodium_memzero' ) ) {
        sodium_memzero( $secret );
      }
    }
  }

  /**
   * Recover a secret using its recovery envelope and re-enroll it
   *
   * @param string $bundleHash
   * @param string $recoveryPassphrase
   * @param StorageOptions|null $options
   * @return void
   */
  public function recoverSecret ( string $bundleHash, string $recoveryPassphrase, ?StorageOptions $options = null ): void {
    if ( $bundleHash === '' ) {
      throw new SecretStorageException( 'Bundle hash cannot be empty' );
    }
    if ( $recoveryPassphrase === '' ) {
      throw new SecretStorageException( 'Recovery passphrase cannot be empty' );
    }

    $raw = $this->backend->getItem( self::RECOVERY_KEY_PREFIX . $bundleHash );
    if ( $raw === null ) {
      throw SecretStorageException::notFound( $bundleHash );
    }

    try {
      $payload = EncryptedSecretPayload::fromJson( $raw );
    } catch ( SecretStorageException ) {
      throw SecretStorageException::decryptionFailed( 'Corrupted recovery payload format' );
    }

    $secret = SecretEnvelope::open( $payload, $recoveryPassphrase );

    try {
      $storePassphrase = $options?->passphrase ?? $this->defaultPassphrase ?? $recoveryPassphrase;
      $storeOptions = new StorageOptions(
        label: $options?->label,
        passphrase: $storePassphrase,
        recoveryPassphrase: $recoveryPassphrase,
        allowUnrecoverable: $options?->allowUnrecoverable ?? false
      );
      $this->storeSecret( $bundleHash, $secret, $storeOptions );
    } finally {
      if ( function_exists( 'sodium_memzero' ) ) {
        sodium_memzero( $secret );
      }
    }
  }
}
