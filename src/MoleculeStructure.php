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
use WishKnish\KnishIO\Client\Exception\CryptoException;
use WishKnish\KnishIO\Client\Exception\MetaMissingException;
use WishKnish\KnishIO\Client\Exception\MoleculeAtomIndexException;
use WishKnish\KnishIO\Client\Exception\MoleculeAtomsMissingException;
use WishKnish\KnishIO\Client\Exception\MoleculeHashMismatchException;
use WishKnish\KnishIO\Client\Exception\MoleculeHashMissingException;
use WishKnish\KnishIO\Client\Exception\MoleculeSignatureMalformedException;
use WishKnish\KnishIO\Client\Exception\MoleculeSignatureMismatchException;
use WishKnish\KnishIO\Client\Exception\TokenTypeException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\TransferMalformedException;
use WishKnish\KnishIO\Client\Exception\TransferMismatchedException;
use WishKnish\KnishIO\Client\Exception\TransferRemainderException;
use WishKnish\KnishIO\Client\Exception\TransferToSelfException;
use WishKnish\KnishIO\Client\Exception\TransferUnbalancedException;
use WishKnish\KnishIO\Client\Exception\TransferWalletException;
use WishKnish\KnishIO\Client\Exception\WalletBatchException;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\Traits\Json;

/**
 * Class MoleculeStructure
 * @package WishKnish\KnishIO\Client
 */
class MoleculeStructure {

    use Json;

    public ?string $molecularHash;
    public ?string $counterparty = null;
    public ?string $bundle;
    public ?string $status;
    public int $local = 0;
    public ?int $height = null;
    public ?int $depth = null;
    public string $createdAt;
    public ?string $receivedAt = null;
    public ?string $processedAt = null;
    public array $atoms = [];
    public ?array $payloads = null;

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
        /*
        return array_values( array_filter( $atoms, static function ( $atom ) use ( $isotopes ) {
          return in_array( $atom->isotope, $isotopes, true );
        } ) );
        */
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
     * @throws CryptoException
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
     * @return void
     * @throws CryptoException
     * @throws MetaMissingException
     * @throws MoleculeAtomIndexException
     * @throws MoleculeAtomsMissingException
     * @throws MoleculeHashMismatchException
     * @throws MoleculeHashMissingException
     * @throws MoleculeSignatureMalformedException
     * @throws MoleculeSignatureMismatchException
     * @throws TokenTypeException
     * @throws TransferBalanceException
     * @throws TransferMalformedException
     * @throws TransferMismatchedException
     * @throws TransferRemainderException
     * @throws TransferToSelfException
     * @throws TransferUnbalancedException
     * @throws TransferWalletException
     * @throws WalletBatchException
     * @throws JsonException
     */
    public function check ( Wallet $senderWallet = null ): void {
        ( new CheckMolecule( $this ) )->verify( $senderWallet );
    }

    /**
     * @return string
     */
    public function __toString (): string {
        return $this->toJson();
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
     * @throws CryptoException
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
        $object = static::arrayToObject( $data );
        foreach ( $object->atoms as $key => $atom_data ) {
            $atom = new Atom( $atom_data[ 'position' ], $atom_data[ 'walletAddress' ], $atom_data[ 'isotope' ] );
            $object->atoms[ $key ] = Atom::arrayToObject( $atom_data, $atom );
        }
        $object->atoms = Atom::sortAtoms( $object->atoms );
        return $object;
    }

    /**
     * @param string $string
     * @param string|null $secret
     *
     * @return MoleculeStructure
     */
    public static function jsonToObject ( string $string, string $secret = null ): static {
        $secret = $secret ?? Crypto::generateSecret();
        $serializer = new Serializer( [ new ObjectNormalizer(), ], [ new JsonEncoder(), ] );
        $object = $serializer->deserialize( $string, static::class, 'json', [ AbstractNormalizer::DEFAULT_CONSTRUCTOR_ARGUMENTS => [ static::class => [ 'secret' => $secret, ], ], ] );

        foreach ( $object->atoms as $idx => $atom ) {
            $object->atoms[ $idx ] = Atom::jsonToObject( $serializer->serialize( $atom, 'json' ) );
        }

        $object->atoms = Atom::sortAtoms( $object->atoms );

        return $object;
    }

    /**
     * @param string $property
     * @param $value
     *
     * @todo change to __set?
     */
    public function setProperty ( string $property, $value ): void {
        $property = array_get( [ 'bundleHash' => 'bundle' ], $property, $property );

        $this->$property = $value;
    }

}
