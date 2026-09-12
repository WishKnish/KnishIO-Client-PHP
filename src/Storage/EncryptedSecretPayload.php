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

use JsonSerializable;

/**
 * Class EncryptedSecretPayload
 * @package WishKnish\KnishIO\Client\Storage
 */
class EncryptedSecretPayload implements JsonSerializable {

  /**
   * @var int
   */
  public int $version;

  /**
   * @var string Base64 encoded
   */
  public string $ciphertext;

  /**
   * @var string Base64 encoded
   */
  public string $iv;

  /**
   * @var string Base64 encoded
   */
  public string $salt;

  /**
   * @var string|null Base64 encoded
   */
  public ?string $tag;

  /**
   * @var string
   */
  public string $algorithm;

  /**
   * @var int
   */
  public int $iterations;

  /**
   * @var SecretStorageMetadata
   */
  public SecretStorageMetadata $metadata;

  /**
   * EncryptedSecretPayload constructor.
   *
   * @param string $ciphertext
   * @param string $iv
   * @param string $salt
   * @param SecretStorageMetadata $metadata
   * @param int $version
   * @param string $algorithm
   * @param int $iterations
   * @param string|null $tag
   */
  public function __construct (
    string $ciphertext,
    string $iv,
    string $salt,
    SecretStorageMetadata $metadata,
    int $version = 1,
    string $algorithm = 'AES-GCM',
    int $iterations = 100000,
    ?string $tag = null
  ) {
    $this->version = $version;
    $this->ciphertext = $ciphertext;
    $this->iv = $iv;
    $this->salt = $salt;
    $this->metadata = $metadata;
    $this->algorithm = $algorithm;
    $this->iterations = $iterations;
    $this->tag = $tag;
  }

  /**
   * @return array<string, mixed>
   */
  public function jsonSerialize (): array {
    $data = [
      'version' => $this->version,
      'ciphertext' => $this->ciphertext,
      'iv' => $this->iv,
      'salt' => $this->salt,
      'algorithm' => $this->algorithm,
      'iterations' => $this->iterations,
      'metadata' => $this->metadata->jsonSerialize(),
    ];

    if ( $this->tag !== null ) {
      $data[ 'tag' ] = $this->tag;
    }

    return $data;
  }

  /**
   * @param array<string, mixed> $data
   * @return self
   */
  public static function fromArray ( array $data ): self {
    $metaRaw = $data[ 'metadata' ] ?? [];
    $metadata = $metaRaw instanceof SecretStorageMetadata
      ? $metaRaw
      : SecretStorageMetadata::fromArray( (array) $metaRaw );

    return new self(
      ciphertext: (string) ( $data[ 'ciphertext' ] ?? '' ),
      iv: (string) ( $data[ 'iv' ] ?? '' ),
      salt: (string) ( $data[ 'salt' ] ?? '' ),
      metadata: $metadata,
      version: (int) ( $data[ 'version' ] ?? 1 ),
      algorithm: (string) ( $data[ 'algorithm' ] ?? 'AES-GCM' ),
      iterations: (int) ( $data[ 'iterations' ] ?? 100000 ),
      tag: isset( $data[ 'tag' ] ) ? (string) $data[ 'tag' ] : null
    );
  }

  /**
   * @param string $json
   * @return self
   */
  public static function fromJson ( string $json ): self {
    $data = json_decode( $json, true );
    if ( !is_array( $data ) ) {
      throw SecretStorageException::decryptionFailed( 'Invalid envelope JSON' );
    }
    return self::fromArray( $data );
  }

  /**
   * @return string
   */
  public function toJson (): string {
    $json = json_encode( $this, JSON_UNESCAPED_SLASHES );
    if ( $json === false ) {
      throw new SecretStorageException( 'Failed to encode envelope to JSON' );
    }
    return $json;
  }
}
