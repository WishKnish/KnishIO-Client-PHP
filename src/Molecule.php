<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use desktopd\SHA3\Sponge as SHA3;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use WishKnish\KnishIO\Client\Exception\BalanceInsufficientException;
use WishKnish\KnishIO\Client\Exception\SignatureMalformedException;
use WishKnish\KnishIO\Client\Exception\SignatureMismatchException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\TransferMalformedException;
use WishKnish\KnishIO\Client\Exception\TransferMismatchedException;
use WishKnish\KnishIO\Client\Exception\MolecularHashMismatchException;
use WishKnish\KnishIO\Client\Exception\MolecularHashMissingException;
use WishKnish\KnishIO\Client\Exception\TransferRemainderException;
use WishKnish\KnishIO\Client\Exception\TransferToSelfException;
use WishKnish\KnishIO\Client\Exception\TransferUnbalancedException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\Traits\Json;
use WishKnish\KnishIO\Client\Exception\AtomsMissingException;

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
	 * @return self
	 * @throws \Exception
	 */
	public function initValue ( Wallet $sourceWallet, Wallet $recipientWallet, Wallet $remainderWallet, $value )
	{
		if ( $sourceWallet->balance - $value < 0 ) {
			throw new BalanceInsufficientException();
		}

		$this->molecularHash = null;
		$idx = count( $this->atoms );

		// Initializing a new Atom to remove tokens from source
		$this->atoms[ $idx ] = new Atom(
			$sourceWallet->position,
			$sourceWallet->address,
			'V',
			$sourceWallet->token,
			-$value,
			null,
			null,
			null,
			null
		);

		// Initializing a new Atom to add tokens to recipient
		$this->atoms[ ++$idx ] = new Atom(
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

		// Initializing a new Atom to deposit remainder in a new wallet
		$this->atoms[ ++$idx ] = new Atom(
			$remainderWallet->position,
			$remainderWallet->address,
			'V',
			$sourceWallet->token,
			$sourceWallet->balance - $value,
			'walletBundle',
			$sourceWallet->bundle,
			null,
			null
		);

		return $this;
	}

	/**
	 * Initialize a C-type molecule to issue a new type of token
	 *
	 * @param Wallet $sourceWallet - wallet signing the transaction. This should ideally be the USER wallet.
	 * @param Wallet $recipientWallet - wallet receiving the tokens. Needs to be initialized for the new token beforehand.
	 * @param integer|float $amount - how many of the token we are initially issuing (for fungible tokens only)
	 * @param array $tokenMeta - additional fields to configure the token
	 * @return self
	 */
	public function initTokenCreation ( Wallet $sourceWallet, Wallet $recipientWallet, $amount, array $tokenMeta )
	{
		$this->molecularHash = null;

		foreach ( [ 'walletAddress', 'walletPosition', ] as $walletKey ) {

			$has = array_filter( $tokenMeta,
				static function ( $token ) use ( $walletKey ) {
					return is_array( $token )
						&& array_key_exists( 'key', $token )
						&& $walletKey === $token[ 'key' ];
				}
			);

			if ( empty( $has ) && !array_key_exists( $walletKey, $tokenMeta ) ) {
				$tokenMeta[ $walletKey ] = $recipientWallet
					->{strtolower( substr( $walletKey, 6 ) )};
			}
		}

		$idx = count( $this->atoms );

		// The primary atom tells the ledger that a certain amount of the new token is being issued.
		$this->atoms[ $idx ] = new Atom(
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

		return $this;
	}

	/**
	 * Initialize an M-type molecule with the given data
	 *
	 * @param Wallet $wallet
	 * @param array $meta
	 * @param string $metaType
	 * @param string|integer $metaId
	 * @return self
	 */
	public function initMeta ( Wallet $wallet, array $meta, $metaType, $metaId )
	{
		$this->molecularHash = null;
		$idx = count( $this->atoms );

		$this->atoms[ $idx ] = new Atom(
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

		return $this;
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
	 * @throws \Exception|\ReflectionException|AtomsMissingException
	 */
	public function sign ( $secret, $anonymous = false )
	{
		if ( empty( $this->atoms ) ||
			!empty( array_filter( $this->atoms,
				static function ( $atom ) {
					return !( $atom instanceof Atom );
				}
			) )
		) {
			throw new AtomsMissingException();
		}

		if ( !$anonymous ) {
			$this->bundle = Crypto::generateBundleHash( $secret );
		}

		$this->molecularHash = Atom::hashAtoms( $this->atoms );

		// Determine first atom
		$firstAtom = $this->atoms[ 0 ];

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
			for ( $iterationCount = 0, $condition = 8 - $normalizedHash[ $idx ]; $iterationCount < $condition; $iterationCount++ ) {
				$workingChunk = bin2hex(
					SHA3::init( SHA3::SHAKE256 )
						->absorb( $workingChunk )
						->squeeze( 64 )
				);
			}
			$signatureFragments .= $workingChunk;
		}

		// Compressing the OTS
		$signatureFragments = Strings::compress( $signatureFragments );

		// Chunking the signature across multiple atoms
		$chunkedSignature = Strings::chunkSubstr( $signatureFragments, ceil( mb_strlen( $signatureFragments ) / count( $this->atoms ) ) );
		$lastPosition = null;

		foreach ( $chunkedSignature as $chunkCount => $chunk ) {
			$this->atoms[ $chunkCount ]->otsFragment = $chunk;
			$lastPosition = $this->atoms[ $chunkCount ]->position;
		}

		return $lastPosition;
	}

	/**
	 * @param string $string
	 * @return object
	 */
	public static function jsonToObject ( $string )
	{
		$serializer = new Serializer( [ new ObjectNormalizer(), ], [ new JsonEncoder(), ] );
		$object = $serializer->deserialize( $string, static::class, 'json' );

		foreach ( $object->atoms as $idx => $atom ) {
			$object->atoms[ $idx ] = Atom::jsonToObject( $serializer->serialize( $atom, 'json' ) );
		}

		return $object;
	}

	/**
	 * @param self $molecule
	 * @param Wallet $senderWallet
	 * @return bool
	 * @throws \ReflectionException|\Exception
	 */
	public static function verify ( self $molecule, Wallet $senderWallet = null )
	{
		return static::verifyMolecularHash( $molecule )
			&& static::verifyOts( $molecule )
			&& static::verifyIsotopeV( $molecule, $senderWallet );
	}

	/**
	 * Verification of V-isotope molecules checks to make sure that:
	 * 1. we're sending and receiving the same token
	 * 2. we're only subtracting on the first atom
	 *
	 * @param self $molecule
	 * @param Wallet $senderWallet
	 * @return bool
	 */
	public static function verifyIsotopeV ( self $molecule, Wallet $senderWallet = null )
	{
		// Do we even have atoms?
		if ( empty( $molecule->atoms ) ) {
			throw new AtomsMissingException();
		}

		// Grabbing the first atom
		reset( $molecule->atoms );
		$firstAtom = current( $molecule->atoms );

		// Looping through each V-isotope atom
		$sum = 0;
		$value = 0;
		foreach ( $molecule->atoms as $index => $vAtom ) {
			// Not V? Next...
			if ( $vAtom->isotope !== 'V' ) {
				continue;
			}

			// Making sure we're in integer land
			$value = 1 * $vAtom->value;

			// Making sure all V atoms of the same token
			if ( $vAtom->token !== $firstAtom->token ) {
				throw new TransferMismatchedException();
			}

			// Checking non-primary atoms
			if ( $index > 0 ) {
				// Negative V atom in a non-primary position?
				if ( $value < 0 ) {
					throw new TransferMalformedException();
				}

				// Cannot be sending and receiving from the same address
				if ( $vAtom->walletAddress === $firstAtom->walletAddress ) {
					throw new TransferToSelfException();
				}
			}

			// Adding this Atom's value to the total sum
			$sum += $value;
		}

		// Does the total sum of all atoms equal the remainder atom's value? (all other atoms must add up to zero)
		if ( $sum !== $value ) {
			throw new TransferUnbalancedException();
		}

		// If we're provided with a senderWallet argument, we can perform additional checks
		if ( $senderWallet ) {
			$remainder = $senderWallet->balance + $firstAtom->value;

			// Is there enough balance to send?
			if ( $remainder < 0 ) {
				throw new TransferBalanceException();
			}

			// Does the remainder match what should be there in the source wallet, if provided?
			if ( $remainder !== $sum ) {
				throw new TransferRemainderException();
			}
		} // No senderWallet, but have a remainder?
		else if ( $value !== 0 ) {
			throw new TransferRemainderException();
		}

		// Looks like we passed all the tests!
		return true;
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
		// No molecular hash?
		if ( $molecule->molecularHash === null ) {
			throw new MolecularHashMissingException();
		}

		// No atoms?
		if ( empty( $molecule->atoms ) ) {
			throw new AtomsMissingException();
		}

		if ( $molecule->molecularHash !== Atom::hashAtoms( $molecule->atoms ) ) {
			throw new MolecularHashMismatchException();
		}

		// Looks like we passed all the tests!
		return true;
	}

	/**
	 * This section describes the function DecodeOtsFragments(Om, Hm), which is used to transform a collection
	 * of signature fragments Om and a molecular hash Hm into a single-use wallet address to be matched against
	 * the sender’s address.
	 *
	 * @param Molecule $molecule
	 * @return bool
	 * @throws \Exception
	 */
	public static function verifyOts ( self $molecule )
	{
		// No molecular hash?
		if ( $molecule->molecularHash === null ) {
			throw new MolecularHashMissingException();
		}

		// No atoms?
		if ( empty( $molecule->atoms ) ) {
			throw new AtomsMissingException();
		}

		// Determine first atom
		$firstAtom = $molecule->atoms[ 0 ];

		// Convert Hm to numeric notation via EnumerateMolecule(Hm)
		$enumeratedHash = static::enumerate( $molecule->molecularHash );
		$normalizedHash = static::normalize( $enumeratedHash );

		// Rebuilding OTS out of all the atoms
		$ots = '';
		foreach ( $molecule->atoms as $atom ) {
			$ots .= $atom->otsFragment;
		}

		// Wrong size? Maybe it's compressed
		if ( mb_strlen( $ots ) !== 2048 ) {
			// Attempt decompression
			$ots = Strings::decompress( $ots );

			// Still wrong? That's a failure
			if ( mb_strlen( $ots ) !== 2048 ) {
				throw new SignatureMalformedException();
			}
		}

		// First atom's wallet is what the molecule must be signed with
		$walletAddress = $firstAtom->walletAddress;

		// Subdivide Kk into 16 segments of 256 bytes (128 characters) each
		$otsChunks = Strings::chunkSubstr( $ots, 128 );

		$keyFragments = '';
		foreach ( $otsChunks as $index => $otsChunk ) {
			// Iterate a number of times equal to 8+Hm[i]
			$workingChunk = $otsChunk;
			for ( $iterationCount = 0, $condition = 8 + $normalizedHash[ $index ]; $iterationCount < $condition; $iterationCount++ ) {
				$workingChunk = bin2hex(
					SHA3::init( SHA3::SHAKE256 )
						->absorb( $workingChunk )
						->squeeze( 64 )
				);
			}
			$keyFragments .= $workingChunk;
		}

		// Absorb the hashed Kk into the sponge to receive the digest Dk
		$digest = bin2hex(
			SHA3::init( SHA3::SHAKE256 )
				->absorb( $keyFragments )
				->squeeze( 1024 )
		);

		// Squeeze the sponge to retrieve a 128 byte (64 character) string that should match the sender’s wallet address
		$address = bin2hex(
			SHA3::init( SHA3::SHAKE256 )
				->absorb( $digest )
				->squeeze( 32 )
		);

		if ( $address !== $walletAddress ) {
			throw new SignatureMismatchException();
		}

		// Looks like we passed all the tests!
		return true;
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
	 * @return array
	 */
	protected static function normalize ( array $mappedHashArray )
	{
		$total = array_sum( $mappedHashArray );
		$totalCondition = $total < 0;

		while ( $total < 0 || $total > 0 ) {
			foreach ( $mappedHashArray as $key => $value ) {
				if ( $totalCondition ? $value < 8 : $value > -8 ) {
					$totalCondition ? [ ++$mappedHashArray[ $key ], ++$total, ] : [ --$mappedHashArray[ $key ], --$total, ];
					if ( $total === 0 ) {
						break;
					}
				}
			}
		}

		return $mappedHashArray;
	}
}
