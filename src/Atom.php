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
use JsonException;
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
   * @return string[]
   */
  public static function getHashableProps(): array {
    return [
      'position',
      'walletAddress',
      'isotope',
      'token',
      'value',
      'batchId',
      'metaType',
      'metaId',
      'meta',
      'createdAt',
    ];
  }

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
   * @param string|null $createdAt
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
   * @param string $isotope
   * @param Wallet|null $wallet
   * @param string|null $value
   * @param string|null $metaType
   * @param string|null $metaId
   * @param AtomMeta|null $meta
   * @param string|null $batchId
   *
   * @return static
   * @throws JsonException
   */
  public static function create(
    string $isotope,
    Wallet $wallet = null,
    string $value = null,
    string $metaType = null,
    string $metaId = null,
    AtomMeta $meta = null,
    string $batchId = null,
  ): self {

    // If meta is not passed - create it
    if ( !$meta ) {
      $meta = new AtomMeta();
    }

    // If wallet has been passed => add related metas
    if ( $wallet ) {
      $meta->setAtomWallet( $wallet );
    }

    // Create the final atom's object
    return new Atom(
      $wallet?->position,
      $wallet?->address,
      $isotope,
      $wallet?->token,
      $value,
      $batchId ?? $wallet?->batchId,
      $metaType,
      $metaId,
      $meta->get(),
    );
  }

  /**
   * @return array
   */
  public function getHashableValues(): array {
    $hashableValues = [];
    foreach( static::getHashableProps() as $property ) {
      $value = $this->$property;

      // All null values not in custom keys list won't get hashed
      if ( $value === null && !in_array( $property, [ 'position', 'walletAddress', ], true ) ) {
        continue;
      }

      // Special code for meta property
      if ( $property === 'meta' ) {
        foreach ( $value as $meta ) {
          if ( isset( $meta[ 'value' ] ) ) {
            $hashableValues[] = ( string ) $meta[ 'key' ];
            $hashableValues[] = ( string ) $meta[ 'value' ];
          }
        }
      }
      // Default value
      else {
        $hashableValues[] = ( string ) $value;
      }
    }
    return $hashableValues;
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

    $hashableValues = [];
    foreach ( $atomList as $atom ) {

      // !!! @todo: why does this code works for every interaction of atoms, maybe it needs to be taken out of the loop?
      $hashableValues[] = (string) $numberOfAtoms;

      $hashableValues = array_merge( $hashableValues, $atom->getHashableValues() );
    }

    // Add hash values to the sponge
    foreach( $hashableValues as $hashableValue ) {
      try {
        $molecularSponge->absorb( $hashableValue );
      }
      catch ( Exception $e ) {
        throw new CryptoException( $e->getMessage(), $e->getCode(), $e );
      }
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
   * @return AtomMeta
   */
  public function getAtomMeta(): AtomMeta {
    return new AtomMeta( $this->aggregatedMeta() );
  }

  /**
   * @param string $property
   * @param $value
   *
   * @todo change to __set?
   */
  public function setProperty ( string $property, $value ): void {
    $property = array_get( [
      'tokenSlug' => 'token',
      'metas' => 'meta',
    ], $property, $property );

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
