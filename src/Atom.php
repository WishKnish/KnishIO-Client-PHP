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

use Exception;
use WishKnish\KnishIO\Client\Exception\CryptoException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\Traits\Json;

/**
 * Class Atom
 * @package WishKnish\KnishIO\Client
 *
 * @property string $position
 * @property string $walletAddress
 * @property string $isotope
 * @property string|null $token
 * @property string|null $value
 * @property string|null $batchId
 * @property string|null $metaType
 * @property string|null $metaId
 * @property array $meta
 * @property integer|null $index
 * @property string|null $otsFragment
 * @property string $createdAt
 *
 */
class Atom {

  use Json;


  /**
   * @param string|null $position
   * @param string|null $walletAddress
   * @param string $isotope
   * @param string|null $token
   * @param string|null $value
   * @param string|null $batchId
   * @param string|null $metaType
   * @param string|null $metaId
   * @param array $meta
   * @param string|null $otsFragment
   * @param int|null $index
   */
  public function __construct (
    public ?string $position,
    public ?string $walletAddress,
    public string $isotope,
    public ?string $token = null,
    public ?string $value = null,
    public ?string $batchId = null,
    public ?string $metaType = null,
    public ?string $metaId = null,
    public array $meta = [],
    public ?string $otsFragment = null,
    public ?int $index = null,
    public ?string $createdAt = null,
  ) {

    // Normalize meta
    if ( $this->meta ) {
      $this->meta = Meta::normalize( $this->meta );
    }

    // Set created at
    if ( !$this->createdAt ) {
      $this->createdAt = Strings::currentTimeMillis();
    }
  }

  /**
   * @param Wallet $wallet
   * @param string $isotope
   * @param int $value
   * @param string|null $metaType
   * @param string|null $metaId
   * @param array $metas
   *
   * @return static
   * @throws \JsonException
   */
  public static function create(
    Wallet $wallet,
    string $isotope,
    string $value = null,
    string $metaType = null,
    string $metaId = null,
    AtomMeta $meta = null,
    string $batchId = null,
  ): self {

    // If meta is not passed - create it
    if ( !$meta ) {
      $meta = new AtomMeta;
    }

    // Create the final atom's object
    return new Atom(
      $wallet->position,
      $wallet->address,
      $isotope,
      $wallet->token,
      $value,
      $batchId ?? $wallet->batchId,
      $metaType,
      $metaId,
      $meta->addWallet( $wallet )
        ->get(),
    );
  }

  /**
   * @param array $atoms
   * @param string $output
   *
   * @return array|string|null
   * @throws CryptoException
   */
  public static function hashAtoms ( array $atoms, string $output = 'base17' ): array|string|null {
    $atomList = static::sortAtoms( $atoms );
    $molecularSponge = Crypto\Shake256::init();
    $numberOfAtoms = count( $atomList );

    $hashingValues = [];
    foreach ( $atomList as $atom ) {

      $atomData = get_object_vars( $atom );

      try {
        // $molecularSponge->absorb( (string) $numberOfAtoms );
        $hashingValues[] = (string) $numberOfAtoms;
      }
      catch ( Exception $e ) {
        throw new CryptoException( $e->getMessage(), $e->getCode(), $e );
      }

      foreach ( $atomData as $name => $value ) {

        // All null values not in custom keys list won't get hashed
        if ( $value === null && !in_array( $name, [ 'position', 'walletAddress', ], true ) ) {
          continue;
        }

        // Excluded keys
        if ( in_array( $name, [ 'otsFragment', 'index', ], true ) ) {
          continue;
        }

        if ( $name === 'meta' ) {
          foreach ( $value as $meta ) {

            if ( isset( $meta[ 'value' ] ) ) {

              try {
                // $molecularSponge->absorb( ( string ) $meta[ 'key' ] );
                // $molecularSponge->absorb( ( string ) $meta[ 'value' ] );
                $hashingValues[] = ( string ) $meta[ 'key' ];
                $hashingValues[] = ( string ) $meta[ 'value' ];
              }
              catch ( Exception $e ) {
                throw new CryptoException( $e->getMessage(), $e->getCode(), $e );
              }

            }
          }

          continue;
        }

        // Absorb value as string
        try {
          // $molecularSponge->absorb( ( string ) $value );
          $hashingValues[] = ( string ) $value;
        }
        catch ( Exception $e ) {
          throw new CryptoException( $e->getMessage(), $e->getCode(), $e );
        }
      }

    }

    // Add hash values to the sponge
    foreach( $hashingValues as $hashingValue ) {
      $molecularSponge->absorb( $hashingValue );
    }

    try {
      switch ( $output ) {
        case 'hex':
        {
          $target = bin2hex( $molecularSponge->squeeze( 32 ) );
          break;
        }
        case 'array':
        {
          $target = str_split( bin2hex( $molecularSponge->squeeze( 32 ) ) );
          break;
        }
        case 'base17':
        {
          $target = str_pad( Strings::charsetBaseConvert( bin2hex( $molecularSponge->squeeze( 32 ) ), 16, 17, '0123456789abcdef', '0123456789abcdefg' ), 64, '0', STR_PAD_LEFT );
          break;
        }
        default:
        {
          $target = null;
        }
      }
    }
    catch ( Exception $e ) {
      throw new CryptoException( $e->getMessage(), $e->getCode(), $e );
    }

    return $target;
  }

  /**
   * @param array $atoms
   *
   * @return array
   */
  public static function sortAtoms ( array $atoms = [] ): array {
    usort( $atoms, static function ( $atom1, $atom2 ) {
      return $atom1->index < $atom2->index ? -1 : 1;
    } );
    return $atoms;
  }

  /**
   * @return array
   */
  public function aggregatedMeta (): array {
    return Meta::aggregate( $this->meta );
  }

  /**
   * @param string $property
   * @param $value
   *
   * @todo change to __set?
   */
  public function setProperty ( string $property, $value ): void {
    $property = array_get( [ 'tokenSlug' => 'token', 'metas' => 'meta', ], $property, $property );

    // Meta json specific logic (if meta does not initialized)
    if ( !$this->meta && $property === 'metasJson' ) {
      $metas = json_decode( $value, true );
      if ( $metas !== null ) {
        $this->meta = Meta::normalize( $metas );
      }
    } // Default meta set
    else {
      $this->$property = $value;
    }
  }

  /**
   * @return int
   */
  public function getValue (): int {
    return $this->value * 1;
  }

}
