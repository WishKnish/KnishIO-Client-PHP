<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use ArrayObject;
use desktopd\SHA3\Sponge as SHA3;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use WishKnish\KnishIO\Client\libraries\Strings;
use WishKnish\KnishIO\Client\Traits\Json;
use WishKnish\KnishIO\Client\Exception\AtomsNotFoundException;

/**
 * Class Molecule
 * @package WishKnish\KnishIO\Client
 *
 * @property string|null $molecularHash
 * @property string|null $cellSlug
 * @property string|null $bundle
 * @property string|null $status
 * @property integer $createdAt
 * @property-read array $atoms
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
     */
    public function __construct ( $cellSlug = null )
    {
        $this->molecularHash = null;
        $this->cellSlug = $cellSlug;
        $this->bundle = null;
        $this->status = null;
        $this->createdAt = Strings::currentTimeMillis();
        $this->atoms = [];
    }

    /**
     * @return string
     */
    public function __toString ()
    {
        return ( string ) $this->toJson();
    }

    /**
     * Initialize a V-type molecule to transfer value from one wallet to another, with a third,
     * regenerated wallet receiving the remainder
     *
     * @param Wallet $sourceWallet
     * @param Wallet $recipientWallet
     * @param Wallet $remainderWallet
     * @param integer|float $value
     * @return array
     * @throws \Exception
     */
    public function initValue ( Wallet $sourceWallet, Wallet $recipientWallet, Wallet $remainderWallet, $value)
    {
        $this->molecularHash = null;
        $idx = count( $this->atoms );

        // Initializing a new Atom to remove tokens from source
        $this->atoms[$idx] = new Atom(
            $sourceWallet->position,
            $sourceWallet->address,
            'V',
            $sourceWallet->token,
            -$value,
            'remainderWallet',
            $remainderWallet->address,
            [ 'remainderPosition' => $remainderWallet->position ],
            null
        );

        $idx++;
        // Initializing a new Atom to add tokens to recipient
        $this->atoms[$idx] = new Atom(
            $recipientWallet->position,
            $recipientWallet->address,
            'V',
            $sourceWallet->token,
            $value,
            'walletBundle',
            $recipientWallet->bundle,
            null,
            null
        );

        return $this->atoms;
    }

    /**
     * Initialize a C-type molecule to issue a new type of token
     *
     * @param Wallet $sourceWallet - wallet signing the transaction. This should ideally be the USER wallet.
     * @param Wallet $recipientWallet - wallet receiving the tokens. Needs to be initialized for the new token beforehand.
     * @param integer|float $amount - how many of the token we are initially issuing (for fungible tokens only)
     * @param array $tokenMeta - additional fields to configure the token
     * @return array
     */
    public function initTokenCreation ( Wallet $sourceWallet, Wallet $recipientWallet, $amount, array $tokenMeta )
    {
        $this->molecularHash = null;

        foreach ( ['walletAddress', 'walletPosition', ] as $walletKey ) {

            $has = array_filter( $tokenMeta, static function ( $token ) use ( $walletKey ) { return is_array( $token ) && array_key_exists('key', $token ) && $walletKey === $token['key']; } );

            if ( empty( $has ) && !array_key_exists( $walletKey, $tokenMeta ) ) {

                $tokenMeta[$walletKey] = $recipientWallet->{ strtolower ( substr ( $walletKey , 6 ) ) };
            }
        }

        $idx = count( $this->atoms );
		// The primary atom tells the ledger that a certain amount of the new token is being issued.
        $this->atoms[$idx] = new Atom(
            $sourceWallet->position,
            $sourceWallet->address,
            'C',
            $sourceWallet->token,
            $amount,
            'token',
            $recipientWallet->token,
            $tokenMeta,
            null
        );

        return $this->atoms;
    }

    /**
     * Initialize an M-type molecule with the given data
     *
     * @param Wallet $wallet
     * @param array $meta
     * @param string $metaType
     * @param string|integer $metaId
     * @return array
     */
    public function initMeta ( Wallet $wallet, array $meta, $metaType, $metaId )
    {
        $this->molecularHash = null;
        $idx = count( $this->atoms );

        $this->atoms[$idx] = new Atom(
            $wallet->position,
            $wallet->address,
            'M',
            $wallet->token,
            null,
            $metaType,
            $metaId,
            $meta,
            null
        );

        return $this->atoms;
    }

    /**
     * Clears the instance of the data, leads the instance to a state equivalent to that after new Molecule()
     *
     * @return self
     */
    public function clear ()
    {
        $this->__construct( $this->cellSlug );
        return $this;
    }

    /**
	 * Creates a one-time signature for a molecule and breaks it up across multiple atoms within that
	 * molecule. Resulting 4096 byte (2048 character) string is the one-time signature, which is then compressed.
	 *
     * @param string $secret
     * @param bool $anonymous
     * @return string
     * @throws \Exception|\ReflectionException|AtomsNotFoundException
     */
    public function sign ( $secret, $anonymous = false )
    {
        if ( empty( $this->atoms ) ||
            !empty( array_filter( $this->atoms, static function ( $atom ) { return !( $atom instanceof Atom ); } ) ) ) {
            throw new AtomsNotFoundException();
        }

        if ( !$anonymous ) {
            $this->bundle = Wallet::generateBundleHash( $secret );
        }

        $this->molecularHash = Atom::hashAtoms( $this->atoms );

        $atoms = ( new ArrayObject( $this->atoms ) )->getArrayCopy();
        ksort( $atoms, SORT_NUMERIC );

        // Determine first atom
        $firstAtom = $atoms[ 0 ];

        // Generate the private signing key for this molecule
        $key = Wallet::generateWalletKey( $secret, $firstAtom->token, $firstAtom->position );

        // Subdivide Kk into 16 segments of 256 bytes (128 characters) each
        $keyChunks = Strings::chunkSubstr( $key, 128 );

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

        // Compressing the OTS
		$signatureFragments = Strings::compress( $signatureFragments );

        // Chunking the signature across multiple atoms
        $chunkedSignature = Strings::chunkSubstr( $signatureFragments, round(strlen( $signatureFragments ) / count( $this->atoms ) ) );
        $lastPosition = null;

        foreach ( $chunkedSignature as $chunkCount => $chunk ) {
            $this->atoms[$chunkCount]->otsFragment = $chunk;
            $lastPosition = $this->atoms[$chunkCount]->position;
        }

        return $lastPosition;
    }

    /**
     * @param string $string
     * @return object
     */
    public static function jsonToObject( $string )
    {
        $serializer =  new Serializer( [ new ObjectNormalizer(), ], [ new JsonEncoder(), ] );
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
        return static::verifyMolecularHash( $molecule ) && static::verifyOts( $molecule ) && static::verifyTokenIsotopeV( $molecule );
    }

    /**
     * @param self $molecule
     * @return bool
     */
    public static function verifyTokenIsotopeV ( self $molecule )
    {
        if ( null !== $molecule->molecularHash && !empty( $molecule->atoms ) ) {

            $vAtoms = array_filter( $molecule->atoms, static function ( Atom $atom ) { return  ( 'V' === $atom->isotope ) ? $atom : false; } );
            $tokens = array_unique( array_map( static function ( Atom $atom ) { return  $atom->token; }, $vAtoms ) );

            foreach ( $tokens as $token ) {
                $atomsToken = array_filter( $vAtoms, static function ( Atom $atom ) use ( $token ) { return  ( $token === $atom->token ) ? $atom : false; } );
                $total = array_sum ( array_map( static function ( Atom $atom ) { return  ( null !== $atom->value ) ? $atom->value * 1 : 0; }, $atomsToken ) );

                if ( 0 !== $total ) {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    /**
	 * Verifies if the hash of all the atoms matches the molecular hash to ensure content has not been messed with
	 *
     * @param self $molecule
     * @return bool
     * @throws \ReflectionException
     */
    public static function verifyMolecularHash ( self $molecule )
    {
        return null !== $molecule->molecularHash && !empty( $molecule->atoms ) && $molecule->molecularHash === Atom::hashAtoms( $molecule->atoms );
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
        if ( null !== $molecule->molecularHash && !empty( $molecule->atoms ) ) {

            $atoms = ( new ArrayObject( $molecule->atoms ) )->getArrayCopy();
            ksort( $atoms, SORT_NUMERIC );

            // Determine first atom
            $first_atom = $atoms[ 0 ];

            // Convert Hm to numeric notation via EnumerateMolecule(Hm)
            $enumerated_hash = static::enumerate( $molecule->molecularHash );
            $normalized_hash = static::normalize( $enumerated_hash );

            // Rebuilding OTS out of all the atoms
            $ots = '';
            foreach ( $molecule->atoms as $atom ) {
                $ots .= $atom->otsFragment;
            }

            // Wrong size? Maybe it's compressed
            if ( strlen( $ots ) !== 2048 ) {
				// Attempt decompression
				$ots = Strings::decompress( $ots );

				// Still wrong? That's a failure
				if ( strlen( $ots ) !== 2048 ) {
					return false;
				}
			}

            // First atom's wallet is what the molecule must be signed with
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

        return false;
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
    protected static function enumerate ( $hash )
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
    protected static function normalize ( array $mapped_hash_array )
    {
        $total = array_sum( $mapped_hash_array );
        $total_condition = $total < 0;

        while ( $total < 0 || $total > 0 ) {

            foreach ( $mapped_hash_array as $key => $value ) {

                $condition = $total_condition ? $value < 8 : $value > -8;

                if ( $condition ) {

                    $total_condition ? [++$mapped_hash_array[$key], ++$total,] : [--$mapped_hash_array[$key], --$total,];

                    if ( 0 === $total ) {
                        break;
                    }
                }
            }
        }

        return $mapped_hash_array;
    }
}
