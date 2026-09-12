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
 * Class MemorySecretStorageProvider
 *
 * In-memory secret storage provider for testing, headless runners,
 * and backward-compatible fallback.
 *
 * @package WishKnish\KnishIO\Client\Storage
 */
class MemorySecretStorageProvider implements SecretStorageProvider {

  /**
   * @var array<string, array{secret: string, metadata: SecretStorageMetadata}>
   */
  protected array $secrets = [];

  /**
   * @var StorageBackend
   */
  protected StorageBackend $backend;

  /**
   * MemorySecretStorageProvider constructor.
   *
   * @param StorageBackend|null $backend
   */
  public function __construct ( ?StorageBackend $backend = null ) {
    $this->backend = $backend ?? new MemoryStorageBackend();
  }

  /**
   * @return StorageBackend
   */
  public function getBackend (): StorageBackend {
    return $this->backend;
  }

  /**
   * @return string
   */
  public function getProviderType (): string {
    return 'memory';
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
    return true;
  }

  /**
   * Store a secret in memory
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

    $metadata = new SecretStorageMetadata(
      bundleHash: $bundleHash,
      label: $options?->label,
      createdAt: (int) round( microtime( true ) * 1000 ),
      hardwareBacked: false,
      providerType: $this->getProviderType()
    );

    $this->secrets[ $bundleHash ] = [
      'secret' => $secret,
      'metadata' => $metadata,
    ];

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
   * Retrieve a secret from memory
   *
   * @param string $bundleHash
   * @param StorageOptions|null $options
   * @return string|null
   */
  public function retrieveSecret ( string $bundleHash, ?StorageOptions $options = null ): ?string {
    return $this->secrets[ $bundleHash ][ 'secret' ] ?? null;
  }

  /**
   * Delete a stored secret
   *
   * @param string $bundleHash
   * @return bool
   */
  public function deleteSecret ( string $bundleHash ): bool {
    $this->backend->removeItem( self::RECOVERY_KEY_PREFIX . $bundleHash );
    if ( !array_key_exists( $bundleHash, $this->secrets ) ) {
      return false;
    }
    unset( $this->secrets[ $bundleHash ] );
    return true;
  }

  /**
   * Check if a secret exists
   *
   * @param string $bundleHash
   * @return bool
   */
  public function hasSecret ( string $bundleHash ): bool {
    return array_key_exists( $bundleHash, $this->secrets );
  }

  /**
   * List all stored secret metadata
   *
   * @return array<int, SecretStorageMetadata>
   */
  public function listSecrets (): array {
    $list = [];
    foreach ( $this->secrets as $entry ) {
      $list[] = $entry[ 'metadata' ];
    }
    return $list;
  }

  /**
   * Execute a callback with the stored secret
   *
   * @template T
   * @param string $bundleHash
   * @param callable(string): T $callback
   * @param StorageOptions|null $options
   * @return T
   */
  public function withSecret ( string $bundleHash, callable $callback, ?StorageOptions $options = null ): mixed {
    if ( !array_key_exists( $bundleHash, $this->secrets ) ) {
      throw SecretStorageException::notFound( $bundleHash );
    }

    $secret = $this->secrets[ $bundleHash ][ 'secret' ];
    return $callback( $secret );
  }

  /**
   * Clear all secrets from memory
   *
   * @return void
   */
  public function clear (): void {
    $this->secrets = [];
    $this->backend->clear();
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
      $storeOptions = new StorageOptions(
        label: $options?->label,
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
