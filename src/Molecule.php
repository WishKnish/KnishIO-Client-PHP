<?php
namespace WishKnish\KnishIO\Client;

use desktopd\SHA3\Sponge as SHA3;
use BI\BigInteger;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use WishKnish\KnishIO\Client\libraries\Str;
use WishKnish\KnishIO\Client\Traits\Json;

/**
 * Class Molecule
 * @package WishKnish\KnishIO\Client
 *
 * @property string|null $molecularHash
 * @property string|null $cellSlug
 * @property string|null $bundle
 * @property string|null $status
 * @property integer $createdAt
 * @property array $atoms
 */
class Molecule
{
    use Json;

    public $molecularHash;
    public $cellSlug;
    public $bundle;
    public $status;
    public $createdAt;
    public $atoms;

    /**
     * Molecule constructor.
     * @param null|string $cellSlug
     * @param null|string $bundle
     */
    public function __construct ( $cellSlug = null, $bundle = null )
    {
        $this->molecularHash = null;
        $this->cellSlug = $cellSlug;
        $this->bundle = $bundle;
        $this->status = null;
        $this->createdAt = time();
        $this->atoms = [];
    }

    /**
     * Initialize a V-type molecule to transfer value from one wallet to another, with a third,
     * regenerated wallet receiving the remainder
     *
     * @param Wallet $sourceWallet
     * @param Wallet $recipientWallet
     * @param Wallet $remainderWallet
     * @param integer|float $value
     * @return string
     * @throws \Exception
     */
    public function initValue ( Wallet $sourceWallet, Wallet $recipientWallet, Wallet $remainderWallet, $value )
    {
        $position = new BigInteger( $sourceWallet->position, 16 );

        $this->atoms = [
            new Atom( $position->toString( 16 ), $sourceWallet->address, 'V', $sourceWallet->token, -$value, 'remainderWallet', $remainderWallet->address ),
            new Atom( $position->add(1)->toString( 16 ), $recipientWallet->address, 'V', $sourceWallet->token, $value, 'walletBundle', $recipientWallet->bundle ),
        ];

        $this->molecularHash = Atom::hashAtoms( $this->atoms );

        return $this->molecularHash;
    }

    /**
     * Initialize a C-type molecule to issue a new type of token
     *
     * @param Wallet $sourceWallet
     * @param Wallet $recipientWallet
     * @param integer|float $amount
     * @param array $tokenMeta
     * @return string
     * @throws \ReflectionException
     */
    public function initTokenCreation ( Wallet $sourceWallet, Wallet $recipientWallet, $amount, array $tokenMeta )
    {
        if ( !array_key_exists( 'walletAddress', $tokenMeta ) ) {

            $tokenMeta['walletAddress'] = $recipientWallet->address;
        }

        $this->atoms = [
            new Atom( $sourceWallet->position, $sourceWallet->address, 'C', $sourceWallet->token, $amount, 'token', $recipientWallet->token, $tokenMeta ),
        ];

        $this->molecularHash = Atom::hashAtoms( $this->atoms );

        return $this->molecularHash;
    }

    /**
     * Initialize an M-type molecule with the given data
     *
     * @param Wallet $wallet
     * @param array $meta
     * @param string $metaType
     * @param string|integer $metaId
     * @return string
     * @throws \ReflectionException
     */
    public function initMeta ( Wallet $wallet, array $meta, $metaType, $metaId )
    {
        $this->atoms = [
            new Atom( $wallet->position, $wallet->address, 'M', $wallet->token, null, $metaType, $metaId, $meta ),
        ];

        $this->molecularHash = Atom::hashAtoms( $this->atoms );

        return $this->molecularHash;
    }

    /**
     * @param string $secret
     * @return string
     * @throws \Exception
     */
    public function sign ( $secret )
    {
        // Determine first atom
        $firstAtom = $this->atoms[0];

        // Generate the private signing key for this molecule
        $key = Wallet::generateWalletKey( $secret, $firstAtom->token, $firstAtom->position );

        // Subdivide Kk into 16 segments of 256 bytes (128 characters) each
        $keyChunks = Str::chunkSubstr( $key, 128 );

        // Convert Hm to numeric notation via EnumerateMolecule(Hm)
        $enumeratedHash = static::enumerate( $this->molecularHash );

        $normalizedHash = static::normalize( $enumeratedHash );

        // Building a one-time-signature
        $signatureFragments = '';

        foreach ( $keyChunks as $idx => $keyChunk ) {
            // Iterate a number of times equal to 8-Hm[i]
            $workingChunk = $keyChunk;

            for ( $iterationCount = 0, $condition = 8 - $normalizedHash[$idx]; $iterationCount < $condition; $iterationCount++ ) {
                $workingChunk = bin2hex( SHA3::init( SHA3::SHAKE256 )->absorb( $workingChunk )->squeeze( 64 ) );
            }

            $signatureFragments .= $workingChunk;
        }

        // Chunking the signature across multiple atoms
        $chunkedSignature = Str::chunkSubstr( $signatureFragments, round(2048 / count( $this->atoms ) ) );
        $lastPosition = null;

        for( $chunkCount = 0, $condition = count( $chunkedSignature ); $chunkCount < $condition; $chunkCount++ ) {

            $this->atoms[$chunkCount]->otsFragment = $chunkedSignature[$chunkCount];
            $lastPosition = $this->atoms[$chunkCount]->position;
        }

        return $lastPosition;
    }

    /**
     * @param $string
     * @return object
     */
    public static function jsonToObject( $string )
    {
        $serializer =  new Serializer( [new ObjectNormalizer(),], [new JsonEncoder(),] );
        $object = $serializer->deserialize( $string, static::class, 'json' );

        foreach ( $object->atoms as $idx => $atom ) {

            $object->atoms[$idx] = Atom::jsonToObject( $serializer->serialize( $atom,  'json') );
        }

        return $object;
    }

    /**
     * @param self $molecule
     * @return bool
     * @throws \ReflectionException|\Exception
     */
    public static function verify ( self $molecule )
    {
        return static::verifyAtoms( $molecule ) && static::verifyMolecularHash( $molecule ) && static::verifyOts( $molecule );
    }

    /**
     * @param self $molecule
     * @return bool
     */
    public static function verifyAtoms ( self $molecule )
    {
        return 0 === array_sum( array_map( static function ( Atom $atom ) { return  ( 'V' === $atom->isotope ) ? $atom->value : 0; }, $molecule->atoms ) );
    }

    /**
     * @param self $molecule
     * @return bool
     * @throws \ReflectionException
     */
    public static function verifyMolecularHash ( self $molecule )
    {
        return $molecule->molecularHash === Atom::hashAtoms( $molecule->atoms );
    }

    /**
     * This section describes the function DecodeOtsFragments(Om, Hm), which is used to transform a collection
     * of signature fragments Om and a molecular hash Hm into a single-use wallet address to be matched against
     * the sender’s address.
     *
     * @param \WishKnish\KnishIO\Client\Molecule $molecule
     * @return bool
     * @throws \Exception
     */
    public static function verifyOts ( self $molecule )
    {
        // Determine first atom
        $first_atom = $molecule->atoms[0];

        // Convert Hm to numeric notation via EnumerateMolecule(Hm)
        $enumerated_hash = static::enumerate( $molecule->molecularHash );
        $normalized_hash = static::normalize( $enumerated_hash );

        // Rebuilding OTS out of all the atoms
        $ots = '';

        foreach ( $molecule->atoms as $atom ) {
            $ots .= $atom->otsFragment;
        }

        $wallet_address = $first_atom->walletAddress;

        // Subdivide Kk into 16 segments of 256 bytes (128 characters) each
        $ots_chunks = str_split( $ots, 128 );

        $key_fragments = '';

        foreach ( $ots_chunks as $index => $ots_chunk ) {

            // Iterate a number of times equal to 8+Hm[i]
            $working_chunk = $ots_chunk;

            for ( $iteration_count = 0, $condition = 8 + $normalized_hash[$index]; $iteration_count < $condition; $iteration_count++) {
                $working_chunk = bin2hex( SHA3::init( SHA3::SHAKE256 )->absorb( $working_chunk )->squeeze(64) );
            }

            $key_fragments .= $working_chunk;
        }
        // Absorb the hashed Kk into the sponge to receive the digest Dk
        $digest = bin2hex( SHA3::init( SHA3::SHAKE256 )->absorb( $key_fragments )->squeeze(1024) );

        // Squeeze the sponge to retrieve a 128 byte (64 character) string that should match the sender’s wallet address
        $address = bin2hex( SHA3::init( SHA3::SHAKE256 )->absorb( $digest )->squeeze(32) );

        return $address === $wallet_address;
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
     * @return array
     */
    public static function enumerate ( $hash )
    {
        $target = [];
        $mapped = [
            '0' => -8, '1' => -7, '2' => -6, '3' => -5, '4' => -4, '5' => -3, '6' => -2, '7' => -1,
            '8' => 0, '9' => 1, 'a' => 2, 'b' => 3, 'c' => 4, 'd' => 5, 'e' => 6, 'f' => 7, 'g' => 8,
        ];

        foreach ( str_split( $hash ) as $index => $symbol ) {
            $lower = strtolower( ( string ) $symbol );

            if ( array_key_exists( $lower, $mapped ) ) {
                $target[$index] = $mapped[$lower];
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
     * @param array $mapped_hash_array
     * @return array
     */
    public static function normalize ( array $mapped_hash_array )
    {
        $total = array_sum( $mapped_hash_array );

        if ( $total > 0 ) {
            while ( $total > 0 ) {
                foreach ( $mapped_hash_array as $key => $value ) {
                    if ( $value > -8 ) {
                        --$mapped_hash_array[$key];
                        --$total;
                        if ( $total === 0 ) {
                            break;
                        }
                    }
                }
            }
        } else {
            while ( $total < 0 ) {
                foreach ( $mapped_hash_array as $key => $value ) {
                    if ( $value < 8 ) {
                        ++$mapped_hash_array[$key];
                        ++$total;
                        if ( $total === 0 ) {
                            break;
                        }
                    }
                }
            }
        }

        return $mapped_hash_array;
    }
}