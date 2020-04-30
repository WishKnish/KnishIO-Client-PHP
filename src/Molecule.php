<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use desktopd\SHA3\Sponge as SHA3;
use Exception;
use ReflectionException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use WishKnish\KnishIO\Client\Exception\BalanceInsufficientException;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Decimal;
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
	 * @return string
	 */
	public static function continuIdMetaType () {
		return 'walletBundle';
	}



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
	 * @param Atom $atom
	 * @return $this
	 */
	public function addAtom (Atom $atom) : self
	{
		$this->molecularHash = null;

		$this->atoms[] =  $atom;

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;
	}


    /**
     * Add user remainder atom
     *
     * @param Wallet $userRemainderWallet
     * @return self
     */
	public function addUserRemainderAtom ( Wallet $userRemainderWallet )
    {
        $this->molecularHash = null;

		// Remainder atom
		$this->atoms[] = new Atom(
			$userRemainderWallet->position,
			$userRemainderWallet->address,
			'I',
			$userRemainderWallet->token,
			null,
			null,
			static::continuIdMetaType(),
			$userRemainderWallet->bundle,
			null,
			$userRemainderWallet->pubkey,
			$userRemainderWallet->characters,
			null,
			$this->generateIndex()
		);

        $this->atoms = Atom::sortAtoms( $this->atoms );

        return $this;
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
	 * @throws BalanceInsufficientException
	 */
	public function initValue ( Wallet $sourceWallet, Wallet $recipientWallet, Wallet $remainderWallet, $value )
	{

		if ( Decimal::cmp( $value, $sourceWallet->balance ) > 0 ) {
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
            $sourceWallet->pubkey,
            $sourceWallet->characters,
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
            $recipientWallet->pubkey,
            $recipientWallet->characters,
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
            $remainderWallet->pubkey,
            $remainderWallet->characters,
			null,
			$this->generateIndex()
		);

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;
	}


	/**
	 * @param Wallet $sourceWallet
	 * @param Wallet $newWallet
	 * @param Wallet $userRemainderWallet
	 * @return $this
	 */
	public function initWalletCreation ( Wallet $sourceWallet, Wallet $newWallet, Wallet $userRemainderWallet )
	{
		$this->molecularHash = null;

		// Metas
		$metas = [
			'address'  => $newWallet->address,
			'token'    => $newWallet->token,
			'bundle'   => $newWallet->bundle,
			'position' => $newWallet->position,
			'amount'   => '0',
			'batch_id' => $newWallet->batchId,
			'pubkey'   => $newWallet->pubkey,
			'characters' => $newWallet->characters,
		];

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
			$metas,
            $sourceWallet->pubkey,
            $sourceWallet->characters,
			null,
			$this->generateIndex()
		);

		// User remainder atom
		$this->addUserRemainderAtom ( $userRemainderWallet );

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;
	}


	/**
	 * Initialize a C-type molecule to issue a new type of token
	 *
	 * @param Wallet $sourceWallet - wallet signing the transaction. This should ideally be the USER wallet.
	 * @param Wallet $recipientWallet - wallet receiving the tokens. Needs to be initialized for the new token beforehand.
     * @param Wallet $userRemainderWallet
	 * @param integer|float $amount - how many of the token we are initially issuing (for fungible tokens only)
	 * @param array $tokenMeta - additional fields to configure the token
	 * @return self
	 */
	public function initTokenCreation ( Wallet $sourceWallet, Wallet $recipientWallet, Wallet $userRemainderWallet, $amount, array $tokenMeta )
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

			if ( empty( $has ) && ! array_key_exists( $walletKey, $tokenMeta ) ) {
				$tokenMeta[ $walletKey ] = $recipientWallet
					->{ strtolower( substr( $walletKey, 6 ) ) };
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
			$sourceWallet->pubkey,
			$sourceWallet->characters,
			null,
			$this->generateIndex()
		);

		// User remainder atom
		$this->addUserRemainderAtom ( $userRemainderWallet );

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;
	}



	/**
	 * Init shadow wallet claim
	 *
	 * @param Wallet $sourceWallet
	 * @param $token
	 * @param $wallets array of Client objectd
	 */
	public function initShadowWalletClaimAtom (Wallet $sourceWallet, $token, array $wallets) {

		$this->molecularHash = null;

		// Generate a wallet metas
		$wallets_metas = [];
		foreach ($wallets as $wallet) {
			$wallets_metas[] = [
				'walletAddress'		=> $wallet->address,
				'walletPosition'	=> $wallet->position,
				'batchId'			=> $wallet->batchId,
			];
		}

		// Create an 'C' atom
		$this->atoms[] = new Atom(
			$sourceWallet->position,
			$sourceWallet->address,
			'C',
			$sourceWallet->token,
			null,
			null,
			'shadowWallet',
			$token,
			['wallets' => json_encode($wallets_metas)],
			$sourceWallet->pubkey,
			$sourceWallet->characters,
			null,
			$this->generateIndex()
		);


		$this->atoms = Atom::sortAtoms( $this->atoms );
	}


    /**
     * Initialize a C-type molecule to issue a new type of identifier
     *
     * @param Wallet $sourceWallet
     * @param Wallet $userRemainderWallet
     * @param string $type
     * @param string $contact
     * @param string $code
     *
     * @return self
     * @throws Exception
     */
	public function initIdentifierCreation ( Wallet $sourceWallet, Wallet $userRemainderWallet, $type, $contact, $code )
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
				'hash' => Crypto::generateBundleHash( trim( $contact ) ),
			],
            $sourceWallet->pubkey,
            $sourceWallet->characters,
			null,
			$this->generateIndex()
		);

		// User remainder atom
		$this->addUserRemainderAtom ( $userRemainderWallet );

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;
	}

	/**
	 * Initialize an M-type molecule with the given data
	 *
	 * @param Wallet $wallet
	 * @param Wallet $userRemainderWallet
	 * @param array $meta
	 * @param string $metaType
	 * @param string|integer $metaId
	 * @return self
	 */
	public function initMeta ( Wallet $wallet, Wallet $userRemainderWallet, array $meta, $metaType, $metaId )
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
			$wallet->pubkey,
			$wallet->characters,
			null,
			$this->generateIndex()
		);

		// User remainder atom
		$this->addUserRemainderAtom($userRemainderWallet);
	}


	/**
	 * Initialize meta append molecule
	 *
	 * @param Wallet $sourceWallet
	 * @param array $meta
	 * @param $metaType
	 * @param $metaId
	 * @return $this
	 */
	public function initMetaAppend ( Wallet $sourceWallet, array $meta, $metaType, $metaId )
	{
		$this->molecularHash = null;

		$this->atoms[] = new Atom(
			$sourceWallet->position,
			$sourceWallet->address,
			'A',
			$sourceWallet->token,
			null,
			null,
			$metaType,
			$metaId,
			$meta,
			$sourceWallet->pubkey,
			$sourceWallet->characters,
			null,
			$this->generateIndex()
		);

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;

	}

    /**
     * @param Wallet $sourceWallet
     * @param Wallet $userRemainderWallet,
     * @param string $token
     * @param int|float $amount
     * @param string $metaType
     * @param string $metaId
     * @param array $meta
     * @return self
     */
	public function initTokenTransfer ( Wallet $sourceWallet, Wallet $userRemainderWallet, $token, $amount, $metaType, $metaId, array $meta = [] )
    {
        $this->molecularHash = null;

        $this->atoms[] = new Atom(
			$sourceWallet->position,
			$sourceWallet->address,
            'T',
			$sourceWallet->token,
            $amount,
            null,
            $metaType,
            $metaId,
            array_merge( $meta, [ 'token' => $token ] ),
			$sourceWallet->pubkey,
			$sourceWallet->characters,
            null,
            $this->generateIndex()
        );

        // User remainder atom
        $this->addUserRemainderAtom ( $userRemainderWallet );

        $this->atoms = Atom::sortAtoms( $this->atoms );

        return $this;
    }

    /**
     * @param Wallet $wallet
     * @param Wallet $userRemainderWallet
     * @return self
     */
    public function initAuthentication ( Wallet $wallet, Wallet $userRemainderWallet )
    {
        $this->molecularHash = null;

        $this->atoms[] = new Atom(
            $wallet->position,
            $wallet->address,
            'U',
            $wallet->token,
            null,
            $wallet->batchId,
            null,
            null,
            null,
            $wallet->pubkey,
            $wallet->characters,
            null,
            $this->generateIndex()
        );

        // User remainder atom
        $this->addUserRemainderAtom ( $userRemainderWallet );

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
	 * @throws Exception|ReflectionException|AtomsMissingException
	 */
	public function sign ( $secret, $anonymous = false, $compressed = true )
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

		$this->atoms = Atom::sortAtoms( $this->atoms );
		$this->molecularHash = Atom::hashAtoms( $this->atoms );

		// Determine first atom
        /** @var Atom $firstAtom */
		$firstAtom = reset( $this->atoms );

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
	 * @throws ReflectionException
	 */
	public function check ( Wallet $senderWallet = null )
	{
		return static::verify( $this, $senderWallet );
	}

	/**
	 * @param Molecule $molecule
	 * @param Wallet $senderWallet
	 * @return bool
	 * @throws ReflectionException|Exception
	 */
	public static function verify ( Molecule $molecule, Wallet $senderWallet = null )
	{

		return CheckMolecule::molecularHash( $molecule )
			&& CheckMolecule::ots( $molecule )
			&& CheckMolecule::index( $molecule )
			&& CheckMolecule::isotopeM( $molecule )
            && CheckMolecule::isotopeC( $molecule )
            && CheckMolecule::isotopeT( $molecule )
            && CheckMolecule::isotopeI( $molecule )
            && CheckMolecule::isotopeU( $molecule )
            && CheckMolecule::continuId( $molecule )
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

		$atom = end( $atoms );

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
