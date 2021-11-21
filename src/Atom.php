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

use ArrayObject;
use Exception;
use JsonException;
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

  public ?string $position;
  public ?string $walletAddress;
  public string $isotope;
  public ?string $token;
  public ?string $value;
  public ?string $batchId;
  public ?string $metaType;
  public ?string $metaId;
  public array $meta = [];
  public ?int $index;
  public ?string $otsFragment;
  public string $createdAt;

  /**
   * Atom constructor.
   *
   * @param string|null $position
   * @param string|null $walletAddress
   * @param string $isotope
   * @param string|null $token
   * @param string|null $value
   * @param string|null $batchId
   * @param string|null $metaType
   * @param string|null $metaId
   * @param array|null $meta
   * @param string|null $otsFragment
   * @param integer|null $index
   */
  public function __construct ( ?string $position, ?string $walletAddress, string $isotope, string $token = null, string $value = null, string $batchId = null, string $metaType = null, string $metaId = null, array $meta = null, string $otsFragment = null, int $index = null ) {
    $this->position = $position;
    $this->walletAddress = $walletAddress;
    $this->isotope = $isotope;
    $this->token = $token;
    $this->value = $value;
    $this->batchId = $batchId;

    $this->metaType = $metaType;
    $this->metaId = $metaId;
    $this->meta = $meta ? Meta::normalizeMeta( $meta ) : [];

    $this->index = $index;
    $this->otsFragment = $otsFragment;
    $this->createdAt = Strings::currentTimeMillis();
  }

  /**
   * @param array $atoms
   * @param string $output
   *
   * @return array|string|null
   * @throws Exception
   */
  public static function hashAtoms ( array $atoms, string $output = 'base17' ): array|string|null {
    $atomList = static::sortAtoms( $atoms );
    $molecularSponge = Crypto\Shake256::init();
    $numberOfAtoms = count( $atomList );

    foreach ( $atomList as $atom ) {

      $atom_data = get_object_vars( $atom );

      $molecularSponge->absorb( $numberOfAtoms );

      foreach ( $atom_data as $name => $value ) {

        // Old atoms support (without batch_id field)
        if ( $value === null && in_array( $name, [ 'batchId', 'pubkey', 'characters', ], true ) ) {
          continue;
        }

        if ( in_array( $name, [ 'otsFragment', 'index', ], true ) ) {
          continue;
        }

        if ( $name === 'meta' ) {
          $list = $value;

          foreach ( $list as $meta ) {

            if ( isset( $meta[ 'value' ] ) ) {

              $molecularSponge->absorb( ( string ) $meta[ 'key' ] );
              $molecularSponge->absorb( ( string ) $meta[ 'value' ] );
            }
          }
          $atom->$name = $list;

          continue;
        }

        if ( in_array( $name, [ 'position', 'walletAddress', 'isotope', ], true ) ) {
          $molecularSponge->absorb( ( string ) $value );
          continue;
        }

        if ( $value !== null ) {
          $molecularSponge->absorb( ( string ) $value );
        }

      }
    }

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

    return $target;
  }

  /**
   * @param array|null $atoms
   *
   * @return array
   */
  public static function sortAtoms ( array $atoms = null ): array {
    $atoms = default_if_null( $atoms, [] );

    $atomList = ( new ArrayObject( $atoms ) )->getArrayCopy();

    usort( $atomList, static function ( self $first, self $second ) {

      if ( $first->index === $second->index ) {
        return 0;
      }

      return $first->index < $second->index ? -1 : 1;

    } );

    return $atomList;
  }

  /**
   * @return array
   */
  public function aggregatedMeta (): array {
    return Meta::aggregateMeta( $this->meta );
  }

  /**
   * @param string $property
   * @param $value
   *
   * @throws JsonException
   * @todo change to __set?
   */
  public function setProperty ( string $property, $value ): void {
    $property = array_get( [ 'tokenSlug' => 'token', 'metas' => 'meta', ], $property, $property );

    // Meta json specific logic (if meta does not initialized)
    if ( !$this->meta && $property === 'metasJson' ) {
      $metas = json_decode( $value, true, 512, JSON_THROW_ON_ERROR );
      if ( $metas !== null ) {
        $this->meta = Meta::normalizeMeta( $metas );
      }
    } // Default meta set
    else {
      $this->$property = $value;
    }
  }

}
