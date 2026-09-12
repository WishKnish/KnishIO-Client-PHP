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
 * Interface SecretStorageProvider
 *
 * Contract for hardware-compatible envelope encryption secret storage providers.
 *
 * @package WishKnish\KnishIO\Client\Storage
 */
interface SecretStorageProvider {

  public const RECOVERY_KEY_PREFIX = 'knishio:recovery:';

  /**
   * Unique identifier of this provider implementation (e.g. "aes-gcm", "memory")
   *
   * @return string
   */
  public function getProviderType (): string;

  /**
   * True only when this provider holds a non-exportable key inside platform-secure
   * hardware (Android TEE/StrongBox, Secure Enclave, TPM) and learned that from the
   * platform itself — never from a caller argument. Software envelope providers
   * return false. The value is persisted as metadata.hardwareBacked in every
   * envelope this provider writes.
   *
   * @return bool
   */
  public function isHardwareBacked (): bool;

  /**
   * Whether the storage backend is available in this runtime
   *
   * @return bool
   */
  public function isAvailable (): bool;

  /**
   * Store and encrypt a master secret for the given bundle hash
   *
   * @param string $bundleHash
   * @param string $secret
   * @param StorageOptions|null $options
   * @return void
   */
  public function storeSecret ( string $bundleHash, string $secret, ?StorageOptions $options = null ): void;

  /**
   * Retrieve and decrypt the master secret for the given bundle hash
   *
   * @param string $bundleHash
   * @param StorageOptions|null $options
   * @return string|null
   */
  public function retrieveSecret ( string $bundleHash, ?StorageOptions $options = null ): ?string;

  /**
   * Delete a stored secret
   *
   * @param string $bundleHash
   * @return bool
   */
  public function deleteSecret ( string $bundleHash ): bool;

  /**
   * Check if a secret exists for the given bundle hash
   *
   * @param string $bundleHash
   * @return bool
   */
  public function hasSecret ( string $bundleHash ): bool;

  /**
   * List all stored secret metadata without exposing plaintext secrets
   *
   * @return array<int, SecretStorageMetadata>
   */
  public function listSecrets (): array;

  /**
   * Execute a callback with the unwrapped secret and zeroize memory upon completion
   *
   * @template T
   * @param string $bundleHash
   * @param callable(string): T $callback
   * @param StorageOptions|null $options
   * @return T
   */
  public function withSecret ( string $bundleHash, callable $callback, ?StorageOptions $options = null ): mixed;

  /**
   * Recover a secret using its recovery envelope and re-enroll it
   *
   * @param string $bundleHash
   * @param string $recoveryPassphrase
   * @param StorageOptions|null $options
   * @return void
   */
  public function recoverSecret ( string $bundleHash, string $recoveryPassphrase, ?StorageOptions $options = null ): void;
}
