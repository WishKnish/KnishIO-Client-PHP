<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use desktopd\SHA3\Sponge as SHA3;
use Exception;
use ReflectionException;
use WishKnish\KnishIO\Client\Exception\BalanceInsufficientException;
use WishKnish\KnishIO\Client\Exception\MetaMissingException;
use WishKnish\KnishIO\Client\Libraries\CheckMolecule;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Decimal;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\Exception\AtomsMissingException;
use WishKnish\KnishIO\Client\Exception\NegativeMeaningException;

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
class Molecule extends MoleculeStructure
{

	private $secret;
	private $sourceWallet;
	private $remainderWallet;


	/**
	 * @return string
	 */
	public static function continuIdMetaType () {
		return 'walletBundle';
	}


    /**
     * Molecule constructor.
     * @param $secret
     * @param $sourceWallet
     * @param null $remainderWallet
     * @param null $cellSlug
     * @throws Exception
     */
	public function __construct ( $secret, $sourceWallet = null, $remainderWallet = null, $cellSlug = null )
	{
		parent::__construct( $cellSlug );

		$this->secret = $secret;
		$this->sourceWallet = $sourceWallet;

		if ( $remainderWallet || $sourceWallet ) {
            $this->remainderWallet = $remainderWallet ?:
                Wallet::create( $secret, $sourceWallet->token, $sourceWallet->batchId, $sourceWallet->characters );
        }


		$this->clear();
	}


	/**
	 * @param MoleculeStructure $moleculeStructure
	 */
	public function fill (MoleculeStructure $moleculeStructure)
	{
		foreach ( get_object_vars($moleculeStructure) as $key => $value ) {
			$this->$key = $value;
		}
	}


	/**
	 * @return mixed
	 */
	public function secret ()
	{
		return $this->secret;
	}


	/**
	 * Source wallet
	 */
	public function sourceWallet ()
	{
		return $this->sourceWallet;
	}


	/**
	 * @return Wallet|WalletShadow
	 */
	public function remainderWallet ()
	{
		return $this->remainderWallet;
	}


	/**
	 * Encrypt message by source wallet
	 *
	 * @param array $data
	 * @param array $shared_wallets
	 */
	public function encryptMessage ( array $data, array $shared_wallets = [] )
	{
		// Merge all args to the common list
		$args = [$data, $this->sourceWallet->pubkey];
		foreach ( $shared_wallets as $shared_wallet ) {
			$args[] = $shared_wallet->pubkey;
		}

		// Call Wallet::encryptMyMessage function
		return call_user_func_array ( [$this->sourceWallet, 'encryptMyMessage'], $args );
	}


	/**
	 * Clears the instance of the data, leads the instance to a state equivalent to that after new Molecule()
	 *
	 * @return self
	 */
	public function clear ()
	{
		$this->molecularHash = null;
		$this->bundle = null;
		$this->status = null;
		$this->createdAt = Strings::currentTimeMillis();
		$this->atoms = [];

		return $this;
	}


	/**
	 * @param Atom $atom
	 * @return $this
	 */
	public function addAtom ( Atom $atom )
	{
		$this->molecularHash = null;

		$this->atoms[] = $atom;

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;
	}


	/**
	 * @param array $metas
	 * @param Wallet|null $wallet
	 * @return array
	 */
    protected function finalMetas ( array $metas = [], Wallet $wallet = null ): array
    {
		$wallet = $wallet ?: $this->sourceWallet;

		$metas[ 'pubkey' ] = $wallet->pubkey;
		$metas[ 'characters' ] = $wallet->characters;

		return $metas;
    }


	/**
	 * @param $metaType
	 * @param $metaId
	 * @param array $metas
	 * @param string $context
	 * @return array
	 */
    protected function contextMetas( $metaType, $metaId, array $metas = [], $context = 'http://www.schema.org' ): array
	{
		$metas[ 'context' ] = $context;

		return $metas;
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
            $this->finalMetas( [], $userRemainderWallet ),
			null,
			$this->generateIndex()
		);

        $this->atoms = Atom::sortAtoms( $this->atoms );

        return $this;
	}

    /**
     * @param integer|float $value
     * @param string $token
     * @param array $metas
     * @return self
     */
    public function replenishingTokens ( $value, $token, array $metas )
    {
        $aggregateMeta = $metas;
        $aggregateMeta[ 'action' ] = 'add';

        foreach ( [ 'address', 'position', 'batchId', ] as $key ) {
            if ( !array_key_exists( $key, $aggregateMeta ) ) {
                throw new MetaMissingException( 'No or not defined "' . $key . '" in meta' );
            }
        }

        $this->molecularHash = null;

        // Initializing a new Atom to remove tokens from source
        $this->atoms[] = new Atom(
            $this->sourceWallet->position,
            $this->sourceWallet->address,
            'C',
            $this->sourceWallet->token,
            $value,
            $this->sourceWallet->batchId,
            'token',
            $token,
            $this->finalMetas( $aggregateMeta ),
            null,
            $this->generateIndex()
        );

        // User remainder atom
        $this->addUserRemainderAtom ( $this->remainderWallet );

        $this->atoms = Atom::sortAtoms( $this->atoms );

        return $this;
    }

    /**
     * @param integer|float $value
     * @param string|null $walletBundle
     * @return self
     * @throws BalanceInsufficientException
     */
    public function burningTokens ( $value, $walletBundle = null  )
    {
        if ( $value < 0.0 ) {
            throw new NegativeMeaningException( 'It is impossible to use a negative value for the number of tokens' );
        }

        if ( Decimal::cmp(  0.0, $this->sourceWallet->balance - $value ) > 0 ) {
            throw new BalanceInsufficientException();
        }

        $this->molecularHash = null;

        // Initializing a new Atom to remove tokens from source
        $this->atoms[] = new Atom(
            $this->sourceWallet->position,
            $this->sourceWallet->address,
            'V',
            $this->sourceWallet->token,
            -$value,
            $this->sourceWallet->batchId,
            null,
            null,
			$this->finalMetas( [] ),
            null,
            $this->generateIndex()
        );

        $this->atoms[] = new Atom(
            $this->remainderWallet->position,
            $this->remainderWallet->address,
            'V',
            $this->sourceWallet->token,
            $this->sourceWallet->balance - $value,
            $this->remainderWallet->batchId,
            $walletBundle ? 'walletBundle' : null,
            $walletBundle,
			$this->finalMetas( [], $this->remainderWallet ),
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
	 * @param Wallet $recipientWallet
	 * @param integer|float $value
	 * @return self
	 * @throws BalanceInsufficientException
	 */
	public function initValue ( Wallet $recipientWallet, $value )
	{

		if ( Decimal::cmp( $value, $this->sourceWallet->balance ) > 0 ) {
			throw new BalanceInsufficientException();
		}

		$this->molecularHash = null;

		// Initializing a new Atom to remove tokens from source
		$this->atoms[] = new Atom(
			$this->sourceWallet->position,
			$this->sourceWallet->address,
			'V',
			$this->sourceWallet->token,
			-$value,
			$this->sourceWallet->batchId,
			null,
			null,
			$this->finalMetas( [] ),
			null,
			$this->generateIndex()
		);

		// Initializing a new Atom to add tokens to recipient
		$this->atoms[] = new Atom(
			$recipientWallet->position,
			$recipientWallet->address,
			'V',
			$this->sourceWallet->token,
			$value,
			$recipientWallet->batchId,
			'walletBundle',
			$recipientWallet->bundle,
			$this->finalMetas( [], $recipientWallet ),
			null,
			$this->generateIndex()
		);

		// Initializing a new Atom to deposit remainder in a new wallet
		$this->atoms[] = new Atom(
			$this->remainderWallet->position,
			$this->remainderWallet->address,
			'V',
			$this->sourceWallet->token,
			$this->sourceWallet->balance - $value,
			$this->remainderWallet->batchId,
			'walletBundle',
			$this->sourceWallet->bundle,
			$this->finalMetas( [], $this->remainderWallet ),
			null,
			$this->generateIndex()
		);

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;
	}


	/**
	 * @param Wallet $newWallet
	 * @return $this
	 */
	public function initWalletCreation ( Wallet $newWallet )
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
		];

		// Create an 'C' atom
		$this->atoms[] = new Atom(
			$this->sourceWallet->position,
			$this->sourceWallet->address,
			'C',
			$this->sourceWallet->token,
			null,
			$this->sourceWallet->batchId,
			'wallet',
			$newWallet->address,
			$this->finalMetas( $metas, $newWallet ),
			null,
			$this->generateIndex()
		);

		// User remainder atom
		$this->addUserRemainderAtom ( $this->remainderWallet );

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;
	}


	/**
	 * Initialize a C-type molecule to issue a new type of token
	 *
	 * @param Wallet $recipientWallet - wallet receiving the tokens. Needs to be initialized for the new token beforehand.
	 * @param integer|float $amount - how many of the token we are initially issuing (for fungible tokens only)
	 * @param array $tokenMeta - additional fields to configure the token
	 * @return self
	 */
	public function initTokenCreation ( Wallet $recipientWallet, $amount, array $tokenMeta )
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
			$this->sourceWallet->position,
			$this->sourceWallet->address,
			'C',
			$this->sourceWallet->token,
			$amount,
			$recipientWallet->batchId,
			'token',
			$recipientWallet->token,
            $this->finalMetas( $tokenMeta ),
			null,
			$this->generateIndex()
		);

		// User remainder atom
		$this->addUserRemainderAtom ( $this->remainderWallet );

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;
	}



	/**
	 * Init shadow wallet claim
	 *
	 * @param $token
	 * @param $wallets array of Client objectd
	 */
	public function initShadowWalletClaimAtom ( $token, array $wallets ) {

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

		$metas = [ 'wallets' => json_encode( $wallets_metas ) ];

		// Create an 'C' atom
		$this->atoms[] = new Atom(
			$this->sourceWallet->position,
			$this->sourceWallet->address,
			'C',
			$this->sourceWallet->token,
			null,
			null,
			'shadowWallet',
			$token,
			$this->finalMetas( $metas ),
			null,
			$this->generateIndex()
		);

		// Add user remainder atom
		$this->addUserRemainderAtom ( $this->remainderWallet );

		$this->atoms = Atom::sortAtoms( $this->atoms );
	}


    /**
     * Initialize a C-type molecule to issue a new type of identifier
     *
     * @param string $type
     * @param string $contact
     * @param string $code
     *
     * @return self
     * @throws Exception
     */
	public function initIdentifierCreation ( $type, $contact, $code )
	{
		$this->molecularHash = null;

		$metas = [
			'code' => $code,
			'hash' => Crypto::generateBundleHash( trim( $contact ) ),
		];

		$this->atoms[] = new Atom(
			$this->sourceWallet->position,
			$this->sourceWallet->address,
			'C',
			$this->sourceWallet->token,
			null,
			null,
			'identifier',
			$type,
			$this->finalMetas( $metas ),
			null,
			$this->generateIndex()
		);

		// User remainder atom
		$this->addUserRemainderAtom ( $this->remainderWallet );

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;
	}

	/**
	 * Initialize an M-type molecule with the given data
	 *
	 * @param array $meta
	 * @param string $metaType
	 * @param string|integer $metaId
	 * @return self
	 */
	public function initMeta ( array $meta, $metaType, $metaId )
	{
		$this->molecularHash = null;

		$this->atoms[] = new Atom(
			$this->sourceWallet->position,
			$this->sourceWallet->address,
			'M',
			$this->sourceWallet->token,
			null,
			$this->sourceWallet->batchId,
			$metaType,
			$metaId,
			$this->finalMetas( $meta ),
			null,
			$this->generateIndex()
		);

		// User remainder atom
		$this->addUserRemainderAtom( $this->remainderWallet );

		$this->atoms = Atom::sortAtoms( $this->atoms );
	}


	/**
	 * @param array $meta
	 */
	public function initBundleMeta ( array $meta )
	{

		// Modify cell slug
		$this->cellSlug = $this->cellSlugOrigin . MoleculeStructure::$cellSlugDelimiter . $this->sourceWallet->bundle;

		// Init meta
		$this->initMeta( $meta, 'walletBundle', $this->sourceWallet->bundle );
	}


	/**
	 * Initialize meta append molecule
	 *
	 * @param array $meta
	 * @param $metaType
	 * @param $metaId
	 * @return $this
	 */
	public function initMetaAppend ( array $meta, $metaType, $metaId )
	{
		$this->molecularHash = null;

		$this->atoms[] = new Atom(
			$this->sourceWallet->position,
			$this->sourceWallet->address,
			'A',
			$this->sourceWallet->token,
			null,
			null,
			$metaType,
			$metaId,
			$this->finalMetas( $meta ),
			null,
			$this->generateIndex()
		);

		// User remainder atom
		$this->addUserRemainderAtom ( $this->remainderWallet );

		$this->atoms = Atom::sortAtoms( $this->atoms );

		return $this;

	}

    /**
     * @param string $token
     * @param int|float $amount
     * @param string $metaType
     * @param string $metaId
     * @param array $meta
     * @return self
     */
	public function initTokenTransfer ( $token, $amount, $metaType, $metaId, array $meta = [] )
    {
        $this->molecularHash = null;


        // Set meta token
		$meta[ 'token' ] = $token;

        $this->atoms[] = new Atom(
			$this->sourceWallet->position,
			$this->sourceWallet->address,
            'T',
			$this->sourceWallet->token,
            $amount,
            null,
            $metaType,
            $metaId,
			$this->finalMetas( $meta ),
            null,
            $this->generateIndex()
        );

        // User remainder atom
        $this->addUserRemainderAtom ( $this->remainderWallet );

        $this->atoms = Atom::sortAtoms( $this->atoms );

        return $this;
    }


	/**
	 * @return $this
	 */
    public function initAuthentication ( )
    {
        $this->molecularHash = null;

        $this->atoms[] = new Atom(
			$this->sourceWallet->position,
			$this->sourceWallet->address,
            'U',
			$this->sourceWallet->token,
            null,
			$this->sourceWallet->batchId,
            null,
            null,
			$this->finalMetas(),
            null,
            $this->generateIndex()
        );

		// User remainder atom
		$this->addUserRemainderAtom ( $this->remainderWallet );

        $this->atoms = Atom::sortAtoms( $this->atoms );

        return $this;
    }


	/**
	 * Creates a one-time signature for a molecule and breaks it up across multiple atoms within that
	 * molecule. Resulting 4096 byte (2048 character) string is the one-time signature, which is then compressed.
	 *
	 * @param bool $anonymous
     * @param bool $compressed
	 * @return string
	 * @throws Exception|ReflectionException|AtomsMissingException
	 */
	public function sign ( $anonymous = false, $compressed = true )
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
			$this->bundle = Crypto::generateBundleHash( $this->secret );
		}

		$this->atoms = Atom::sortAtoms( $this->atoms );
		$this->molecularHash = Atom::hashAtoms( $this->atoms );

		// Determine first atom
        /** @var Atom $firstAtom */
		$firstAtom = reset( $this->atoms );

		// Generate the private signing key for this molecule
		$key = Wallet::generateWalletKey( $this->secret, $firstAtom->token, $firstAtom->position );

		// Building a one-time-signature
		$signatureFragments = $this->signatureFragments( $key );

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


}
