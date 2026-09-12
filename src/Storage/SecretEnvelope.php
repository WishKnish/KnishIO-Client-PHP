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
 * Class SecretEnvelope
 *
 * Implements the cross-SDK at-rest envelope:
 * PBKDF2-HMAC-SHA256 x 100,000 (16-byte salt) -> AES-256-GCM (12-byte IV, 128-bit tag appended),
 * standard padded Base64, camelCase metadata.
 *
 * @package WishKnish\KnishIO\Client\Storage
 */
class SecretEnvelope {

  public const ALGORITHM = 'AES-GCM';
  public const DEFAULT_ITERATIONS = 100000;
  public const GCM_TAG_LENGTH = 16;
  public const GCM_IV_LENGTH = 12;
  public const SALT_LENGTH = 16;

  /**
   * Derive an AES-256 key from a passphrase and salt using PBKDF2-HMAC-SHA256
   *
   * @param string $passphrase
   * @param string $salt
   * @param int $iterations
   * @return string Raw 32-byte key
   */
  public static function deriveKey ( string $passphrase, string $salt, int $iterations = self::DEFAULT_ITERATIONS ): string {
    return hash_pbkdf2( 'sha256', $passphrase, $salt, $iterations, 32, true );
  }

  /**
   * Seal a plaintext secret into an EncryptedSecretPayload envelope
   *
   * @param string $secret
   * @param string $passphrase
   * @param SecretStorageMetadata $metadata
   * @param int $iterations
   * @return EncryptedSecretPayload
   */
  public static function seal (
    string $secret,
    string $passphrase,
    SecretStorageMetadata $metadata,
    int $iterations = self::DEFAULT_ITERATIONS
  ): EncryptedSecretPayload {
    $salt = random_bytes( self::SALT_LENGTH );
    $iv = random_bytes( self::GCM_IV_LENGTH );
    $key = self::deriveKey( $passphrase, $salt, $iterations );

    $tag = '';
    $ciphertext = openssl_encrypt( $secret, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag, '', self::GCM_TAG_LENGTH );
    if ( function_exists( 'sodium_memzero' ) ) {
      sodium_memzero( $key );
    }

    if ( $ciphertext === false ) {
      throw new SecretStorageException( 'OpenSSL AES-GCM encryption failed: ' . ( openssl_error_string() ?: 'unknown error' ) );
    }

    $combined = $ciphertext . $tag;

    return new EncryptedSecretPayload(
      ciphertext: base64_encode( $combined ),
      iv: base64_encode( $iv ),
      salt: base64_encode( $salt ),
      metadata: $metadata,
      version: 1,
      algorithm: self::ALGORITHM,
      iterations: $iterations
    );
  }

  /**
   * Open an EncryptedSecretPayload envelope with a passphrase, returning the decrypted secret
   *
   * @param EncryptedSecretPayload $payload
   * @param string $passphrase
   * @return string Plaintext secret
   */
  public static function open ( EncryptedSecretPayload $payload, string $passphrase ): string {
    $salt = base64_decode( $payload->salt, true );
    $iv = base64_decode( $payload->iv, true );
    $rawCiphertext = base64_decode( $payload->ciphertext, true );

    if ( $salt === false || $iv === false || $rawCiphertext === false ) {
      throw SecretStorageException::decryptionFailed( 'Invalid base64 encoding in envelope payload' );
    }

    if ( $payload->tag !== null ) {
      $tag = base64_decode( $payload->tag, true );
      if ( $tag === false ) {
        throw SecretStorageException::decryptionFailed( 'Invalid base64 encoding in envelope tag' );
      }
      $ct = $rawCiphertext;
    } else {
      if ( strlen( $rawCiphertext ) < self::GCM_TAG_LENGTH ) {
        throw SecretStorageException::decryptionFailed( 'Ciphertext too short to contain authentication tag' );
      }
      $ct = substr( $rawCiphertext, 0, -self::GCM_TAG_LENGTH );
      $tag = substr( $rawCiphertext, -self::GCM_TAG_LENGTH );
    }

    $iterations = $payload->iterations > 0 ? $payload->iterations : self::DEFAULT_ITERATIONS;
    $key = self::deriveKey( $passphrase, $salt, $iterations );

    $plaintext = openssl_decrypt( $ct, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );
    if ( function_exists( 'sodium_memzero' ) ) {
      sodium_memzero( $key );
    }

    if ( $plaintext === false ) {
      throw SecretStorageException::decryptionFailed( 'Authentication tag verification failed or wrong passphrase' );
    }

    return $plaintext;
  }
}
