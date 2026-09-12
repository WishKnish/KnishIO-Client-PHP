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
 * Class SecretStorageMetadata
 * @package WishKnish\KnishIO\Client\Storage
 */
class SecretStorageMetadata implements JsonSerializable {

  /**
   * @var string
   */
  public string $bundleHash;

  /**
   * @var string|null
   */
  public ?string $label;

  /**
   * @var int
   */
  public int $createdAt;

  /**
   * @var bool
   */
  public bool $hardwareBacked;

  /**
   * @var string
   */
  public string $providerType;

  /**
   * SecretStorageMetadata constructor.
   *
   * @param string $bundleHash
   * @param string|null $label
   * @param int|null $createdAt Timestamp in milliseconds
   * @param bool $hardwareBacked
   * @param string $providerType
   */
  public function __construct (
    string $bundleHash,
    ?string $label = null,
    ?int $createdAt = null,
    bool $hardwareBacked = false,
    string $providerType = 'aes-gcm'
  ) {
    $this->bundleHash = $bundleHash;
    $this->label = $label;
    $this->createdAt = $createdAt ?? (int) round( microtime( true ) * 1000 );
    $this->hardwareBacked = $hardwareBacked;
    $this->providerType = $providerType;
  }

  /**
   * Serializes metadata with strict camelCase keys and omits label when null.
   *
   * @return array<string, mixed>
   */
  public function jsonSerialize (): array {
    $data = [
      'bundleHash' => $this->bundleHash,
      'createdAt' => $this->createdAt,
      'hardwareBacked' => $this->hardwareBacked,
      'providerType' => $this->providerType,
    ];

    if ( $this->label !== null ) {
      $data[ 'label' ] = $this->label;
    }

    return $data;
  }

  /**
   * Instantiate from array with camelCase or snake_case fallback
   *
   * @param array<string, mixed> $data
   * @return self
   */
  public static function fromArray ( array $data ): self {
    $createdAt = null;
    if ( isset( $data[ 'createdAt' ] ) ) {
      $createdAt = (int) $data[ 'createdAt' ];
    } elseif ( isset( $data[ 'created_at' ] ) ) {
      $createdAt = (int) $data[ 'created_at' ];
    }

    return new self(
      bundleHash: (string) ( $data[ 'bundleHash' ] ?? $data[ 'bundle_hash' ] ?? '' ),
      label: isset( $data[ 'label' ] ) ? (string) $data[ 'label' ] : null,
      createdAt: $createdAt,
      hardwareBacked: (bool) ( $data[ 'hardwareBacked' ] ?? $data[ 'hardware_backed' ] ?? false ),
      providerType: (string) ( $data[ 'providerType' ] ?? $data[ 'provider_type' ] ?? 'aes-gcm' )
    );
  }
}
