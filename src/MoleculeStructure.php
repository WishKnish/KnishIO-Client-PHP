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

use JsonException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\Traits\Json;

/**
 * Class MoleculeStructure
 * @package WishKnish\KnishIO\Client
 */
class MoleculeStructure {

  /**
   * @var string|null
   */
  public ?string $molecularHash;

  /**
   * @var string|null
   */
  public ?string $counterparty = null;

  /**
   * @var string|null
   */
  public ?string $bundle;

  /**
   * @var string|null
   */
  public ?string $status;

  /**
   * @var int
   */
  public int $local = 0;

  /**
   * @var string
   */
  public string $createdAt;

  /**
   * @var array
   */
  public array $atoms = [];

  /**
   * @param string|array $isotopes
   * @param array $atoms
   *
   * @return array
   */
  public static function isotopeFilter ( string|array $isotopes, array $atoms ): array {
    if ( is_string( $isotopes ) ) {
      $isotopes = [ $isotopes ];
    }
    $result = [];
    foreach ( $atoms as $atom ) {
      if ( in_array( $atom->isotope, $isotopes, true ) ) {
        $result[] = $atom;
      }
    }
    return $result;
  }

  /**
   * @param string|array $isotopes
   *
   * @return array
   */
  public function getIsotopes ( string|array $isotopes ): array {
    return static::isotopeFilter( $isotopes, $this->atoms );
  }

  /**
   * @return string
   */
  public function logString (): string {
    return $this->molecularHash . ' [ ' . implode( ',', array_column( $this->atoms, 'isotope' ) ) . ' ] ';
  }

  /**
   * @param string|null $counterparty
   *
   * @return $this
   */
  public function withCounterparty ( ?string $counterparty ): self {
    $this->counterparty = $counterparty;
    return $this;
  }

  /**
   * @param int $index
   *
   * @return string
   */
  public function getBatchId ( int $index ): string {
    $molecularHash = Atom::hashAtoms( $this->atoms );
    return Crypto::generateBatchId( $molecularHash, $index );
  }

  /**
   * This algorithm describes the function EnumerateMolecule(Hm), designed to accept a pseudo-hexadecimal string Hm, and output a collection of decimals representing each character.
   * Molecular hash Hm is presented as a 128 byte (64-character) pseudo-hexadecimal string featuring numbers from 0 to 9 and characters from A to F - a total of 15 unique symbols.
   * To ensure that Hm has an even number of symbols, convert it to Base 17 (adding G as a possible symbol).
   * Map each symbol to integer values as follows:
   * 0   1    2   3   4   5   6   7   8  9  A   B   C   D   E   F   G
   * -8  -7  -6  -5  -4  -3  -2  -1  0   1   2   3   4   5   6   7   8
   *
   * @param string $hash
   *
   * @return array
   */
  protected static function enumerate ( string $hash ): array {

    $target = [];
    $mapped = [
      '0' => -8,
      '1' => -7,
      '2' => -6,
      '3' => -5,
      '4' => -4,
      '5' => -3,
      '6' => -2,
      '7' => -1,
      '8' => 0,
      '9' => 1,
      'a' => 2,
      'b' => 3,
      'c' => 4,
      'd' => 5,
      'e' => 6,
      'f' => 7,
      'g' => 8,
    ];

    foreach ( str_split( $hash ) as $index => $symbol ) {

      $lower = strtolower( ( string ) $symbol );

      if ( array_key_exists( $lower, $mapped ) ) {
        $target[ $index ] = $mapped[ $lower ];
      }
    }

    return $target;
  }

  /**
   * Normalize Hm to ensure that the total sum of all symbols is exactly zero. This ensures that exactly 50% of the WOTS+ key is leaked with each usage, ensuring predictable key safety:
   * The sum of each symbol within Hm shall be presented by m
   * While m0 iterate across that set’s integers as Im:
   * If m0 and Im>-8 , let Im=Im-1
   * If m<0 and Im<8 , let Im=Im+1
   * If m=0, stop the iteration
   *
   * @param array $mappedHashArray
   *
   * @return array
   */
  protected static function normalize ( array $mappedHashArray ): array {

    $total = array_sum( $mappedHashArray );
    $totalCondition = $total < 0;

    while ( $total !== 0 ) {

      foreach ( $mappedHashArray as $key => $value ) {

        if ( $totalCondition ? $value < 8 : $value > -8 ) {

          $totalCondition ? [
            ++$mappedHashArray[ $key ],
            ++$total,
          ] : [
            --$mappedHashArray[ $key ],
            --$total,
          ];

          if ( $total === 0 ) {
            break;
          }
        }
      }
    }

    return $mappedHashArray;
  }

  /**
   * @param string|null $cellSlug
   */
  public function __construct ( public ?string $cellSlug = null ) {

  }

  /**
   * @param Wallet|null $senderWallet
   *
   * @throws JsonException
   */
  public function check ( Wallet $senderWallet = null ): void {
    ( new CheckMolecule( $this ) )->verify( $senderWallet );
  }

  /**
   * @return array
   */
  public function normalizedHash (): array {
    // Convert Hm to numeric notation via EnumerateMolecule(Hm)
    return static::normalize( static::enumerate( $this->molecularHash ) );
  }

  /**
   * @param string $key
   * @param bool $encode
   *
   * @return string
   */
  public function signatureFragments ( string $key, bool $encode = true ): string {
    // Subdivide Kk into 16 segments of 256 bytes (128 characters) each
    $keyChunks = Strings::chunkSubstr( $key, 128 );

    // Convert Hm to numeric notation via EnumerateMolecule(Hm)
    $normalizedHash = $this->normalizedHash();

    // Building a one-time-signature
    $signatureFragments = '';
    foreach ( $keyChunks as $idx => $workingChunk ) {

      // Iterate a number of times equal to 8-Hm[i]
      for ( $iterationCount = 0, $condition = 8 + $normalizedHash[ $idx ] * ( $encode ? -1 : 1 ); $iterationCount < $condition; $iterationCount++ ) {

        $workingChunk = bin2hex( Crypto\Shake256::hash( $workingChunk, 64 ) );
      }

      $signatureFragments .= $workingChunk;
    }

    return $signatureFragments;
  }

  /**
   * @param array $data
   *
   * @return static
   */
  public static function toObject ( array $data ): MoleculeStructure {
    $molecule = new self;
    foreach( [
      'cellSlug', 'molecularHash', 'counterparty', 'bundle', 'status', 'local', 'createdAt',
    ] as $property ) {
      $molecule->$property = array_get( $data, $property );
    }
    foreach( array_get( $data, 'atoms', [] ) as $atom ) {
      if ( !array_has( $atom, 'meta' ) ) {
        dd( $atom );
      }
      $molecule->atoms[] = new Atom(
        array_get( $atom, 'position' ),
        array_get( $atom, 'walletAddress' ),
        array_get( $atom, 'isotope' ),
        array_get( $atom, 'token' ),
        array_get( $atom, 'value' ),
        array_get( $atom, 'batchId' ),
        array_get( $atom, 'metaType' ),
        array_get( $atom, 'metaId' ),
        array_get( $atom, 'meta' ),
        array_get( $atom, 'otsFragment' ),
        array_get( $atom, 'index' ),
        array_get( $atom, 'createdAt' ),
      );
    }
    $molecule->atoms = Atom::sortAtoms( $molecule->atoms );
    return $molecule;
  }

}
