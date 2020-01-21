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
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
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

		// Initializing a new Atom to remove tokens from source
		$this->atoms[] = new Atom(
			$sourceWallet->position,
			$sourceWallet->address,
			'V',
			$sourceWallet->token,
			-$value,
			$sourceWallet->batchId,
			null,
			null,
			null,
			null,
			$this->generateIndex()
		);

		// Initializing a new Atom to add tokens to recipient
		$this->atoms[] = new Atom(
			$recipientWallet->position,
			$recipientWallet->address,
			'V',
			$sourceWallet->token,
			$value,
			$recipientWallet->batchId,
			'walletBundle',
			$recipientWallet->bundle,
			null,
			null,
			$this->generateIndex()
		);

		// Initializing a new Atom to deposit remainder in a new wallet
		$this->atoms[] = new Atom(
			$remainderWallet->position,
			$remainderWallet->address,
			'V',
			$sourceWallet->token,
			$sourceWallet->balance - $value,
			$remainderWallet->batchId,
			'walletBundle',
			$sourceWallet->bundle,
			null,
			null,
			$this->generateIndex()
		);

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;

	}

	/**
	 * @param Wallet $sourceWallet
	 * @param Wallet $newWallet
	 * @return self
	 */
	public function initWalletCreation ( Wallet $sourceWallet, Wallet $newWallet )
	{
		$this->molecularHash = null;

		// Create an 'C' atom
		$this->atoms[] = new Atom(
			$sourceWallet->position,
			$sourceWallet->address,
			'C',
			$sourceWallet->token,
			null,
			$sourceWallet->batchId,
			'wallet',
			$newWallet->address,
			[
				'address'  => $newWallet->address,
				'token'    => $newWallet->token,
				'bundle'   => $newWallet->bundle,
				'position' => $newWallet->position,
				'amount'   => '0',
				'batch_id' => $newWallet->batchId,
			],
			null,
			$this->generateIndex()
		);

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;
	}

	/**
	 * Initialize a C-type molecule to issue a new type of identifier
	 *
	 * @param Wallet $sourceWallet
	 * @param string $source
	 * @param string $type
	 * @param string $code
	 *
	 * @return self
	 */
	public function initIdentifierCreation ( Wallet $sourceWallet, $source, $type, $code )
	{

		$this->molecularHash = null;

		$this->atoms[] = new Atom(
			$sourceWallet->position,
			$sourceWallet->address,
			'C',
			$sourceWallet->token,
			null,
			null,
			'identifier',
			$type,
			[
				'code' => $code,
				'hash' => Crypto::generateBundleHash( \trim( $source ) ),
			],
			null,
			$this->generateIndex()
		);

		$this->atoms = Atom::sortAtoms( $this->atoms );

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

			$has = \array_filter( $tokenMeta,
				static function ( $token ) use ( $walletKey ) {

					return \is_array( $token )
						&& \array_key_exists( 'key', $token )
						&& $walletKey === $token[ 'key' ];

				}
			);

			if ( empty( $has ) && !\array_key_exists( $walletKey, $tokenMeta ) ) {

				$tokenMeta[ $walletKey ] = $recipientWallet
					->{\strtolower( \substr( $walletKey, 6 ) )};

			}

		}

		// The primary atom tells the ledger that a certain amount of the new token is being issued.
		$this->atoms[] = new Atom(
			$sourceWallet->position,
			$sourceWallet->address,
			'C',
			$sourceWallet->token,
			$amount,
			$recipientWallet->batchId,
			'token',
			$recipientWallet->token,
			$tokenMeta,
			null,
			$this->generateIndex()
		);

		$this->atoms = Atom::sortAtoms( $this->atoms );

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

		$this->atoms[] = new Atom(
			$wallet->position,
			$wallet->address,
			'M',
			$wallet->token,
			null,
			$wallet->batchId,
			$metaType,
			$metaId,
			$meta,
			null,
			$this->generateIndex()
		);

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;

	}

    /**
     * @param string $secret
     * @param string $token
     * @param int|float $amount
     * @param string $metaType
     * @param string $metaId
     * @param array $meta
     * @return self
     * @throws \Exception
     */
    public function initTokenTransfer ( $secret, $token, $amount, $metaType, $metaId, array $meta = [] )
    {

        $this->molecularHash = null;

        $wallet = new Wallet( $secret, $token );

        $this->atoms[] = new Atom(
            $wallet->position,
            $wallet->address,
            'T',
            $wallet->token,
            $amount,
            $wallet->batchId,
            $metaType,
            $metaId,
            $meta,
            null,
            $this->generateIndex()
        );

        $this->atoms = Atom::sortAtoms( $this->atoms );

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
	 * @param bool $compressed
	 * @return string
	 * @throws \Exception|\ReflectionException|AtomsMissingException
	 */
	public function sign ( $secret, $anonymous = false, $compressed = true )
	{
		if ( empty( $this->atoms ) ||
			!empty( \array_filter( $this->atoms,
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

		$this->atoms = Atom::sortAtoms( $this->atoms );
		$this->molecularHash = Atom::hashAtoms( $this->atoms );

		// Determine first atom
		$firstAtom = \reset( $this->atoms );

		// Generate the private signing key for this molecule
		$key = Wallet::generateWalletKey( $secret, $firstAtom->token, $firstAtom->position );

		// Subdivide Kk into 16 segments of 256 bytes (128 characters) each
		$keyChunks = Strings::chunkSubstr( $key, 128 );

		// Convert Hm to numeric notation via EnumerateMolecule(Hm)
		$normalizedHash = CheckMolecule::normalizedHash( $this->molecularHash );

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
		if ( $compressed ) {

			$signatureFragments = Strings::hexToBase64( $signatureFragments );

		}

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
	 * @param Wallet|null $senderWallet
	 * @return bool
	 * @throws \ReflectionException
	 */
	public function check ( Wallet $senderWallet = null )
	{
		return static::verify( $this, $senderWallet );
	}

	/**
	 * @param Molecule $molecule
	 * @param Wallet $senderWallet
	 * @return bool
	 * @throws \ReflectionException|\Exception
	 */
	public static function verify ( Molecule $molecule, Wallet $senderWallet = null )
	{

		return CheckMolecule::molecularHash( $molecule )
			&& CheckMolecule::ots( $molecule )
			&& CheckMolecule::index( $molecule )
			&& CheckMolecule::isotopeM( $molecule )
            && CheckMolecule::isotopeT( $molecule )
			&& CheckMolecule::isotopeV( $molecule, $senderWallet );

	}

	/**
	 * @return int
	 */
	public function generateIndex ()
	{

		return static::generateNextAtomIndex( $this->atoms );

	}

	/**
	 * @param array $atoms
	 * @return int
	 */
	public static function generateNextAtomIndex ( array $atoms = [] )
	{

		$atom = \end( $atoms );

		return ( false === $atom ) ? 0 : $atom->index + 1;

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

		$object->atoms = Atom::sortAtoms( $object->atoms );

		return $object;

	}

}
