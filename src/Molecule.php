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
use SodiumException;
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Exception\MetaMissingException;
use WishKnish\KnishIO\Client\Exception\MoleculeAtomsMissingException;
use WishKnish\KnishIO\Client\Exception\TransferAmountException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\WalletSignatureException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Strings;

/**
 * Class Molecule
 * @package WishKnish\KnishIO\Client
 *
 * @property string|null $molecularHash
 * @property string|null $cellSlug
 * @property string|null $bundleHash
 * @property string|null $status
 * @property string $createdAt
 * @property array $atoms
 */
class Molecule extends MoleculeStructure {

    /**
     * @param string $secret
     * @param Wallet|null $sourceWallet
     * @param Wallet|null $remainderWallet
     * @param string|null $cellSlug
     *
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function __construct (
        private string $secret,
        private ?Wallet $sourceWallet = null,
        private ?Wallet $remainderWallet = null,
        ?string $cellSlug = null
    ) {
        parent::__construct( $cellSlug );

        // Generates remainder wallet if source wallet is provided
        if ( $remainderWallet || $sourceWallet ) {
            $this->remainderWallet = $remainderWallet ?: Wallet::create( $secret, $sourceWallet->tokenSlug, $sourceWallet->batchId, $sourceWallet->characters );
        }

        $this->clear();
    }

    /**
     * Clears the instance of the data, leads the instance to a state equivalent to that after new Molecule()
     *
     * @return self
     */
    public function clear (): Molecule {
        $this->molecularHash = null;
        $this->bundleHash = null;
        $this->status = null;
        $this->createdAt = Strings::currentTimeMillis();
        $this->atoms = [];

        return $this;
    }

    /**
     * @param MoleculeStructure $moleculeStructure
     */
    public function fill ( MoleculeStructure $moleculeStructure ): void {
        foreach ( get_object_vars( $moleculeStructure ) as $key => $value ) {
            $this->$key = $value;
        }
    }

    /**
     * Source wallet
     */
    public function sourceWallet (): ?Wallet {
        return $this->sourceWallet;
    }

    /**
     * @return Wallet|null
     */
    public function remainderWallet (): ?Wallet {
        return $this->remainderWallet;
    }

    /**
     * @param string $metaType
     * @param string $metaId
     * @param array $metas
     * @param array $policy
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function addPolicyAtom (
        string $metaType,
        string $metaId,
        array $metas = [],
        array $policy = []
    ): self {

        // AtomMeta object initialization
        $atomMeta = new AtomMeta( $metas );
        $atomMeta->addPolicy( $policy );

        $this->addAtom( Atom::create(
            'R',
            null,
            null,
            $metaType,
            $metaId,
            $atomMeta
        ) );

        return $this;
    }

    /**
     * @param Atom $atom
     *
     * @return $this
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function addAtom ( Atom $atom ): Molecule {

        // Reset molecular hash
        $this->molecularHash = null;

        // Set atom's index
        $atom->index = $this->generateIndex();

        // Add source wallet if not already set when adding first atom
        if ( !$this->sourceWallet && ( count( $this->atoms ) === 0 ) ) {
            $this->sourceWallet = new Wallet( $this->secret(), $atom->tokenSlug, $atom->walletPosition, $atom->batchId );
        }

        // Add atom
        $this->atoms[] = $atom;

        // Sort atoms
        $this->atoms = Atom::sortAtoms( $this->atoms );

        return $this;
    }

    /**
     * @return int
     */
    public function generateIndex (): int {
        return static::generateNextAtomIndex( $this->atoms );
    }

    /**
     * @param array $atoms
     *
     * @return int
     */
    public static function generateNextAtomIndex ( array $atoms = [] ): int {
        return count( $atoms );
    }

    /**
     * @return string
     */
    public function secret (): string {
        return $this->secret;
    }

    /**
     * @param string $metaType
     * @param string $metaId
     * @param array $metas
     * @param array $policy
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function createRule (
        string $metaType,
        string $metaId,
        array $metas,
        array $policy = []
    ): Molecule {

        foreach ( [
            'conditions',
            'callback',
            'rule'
        ] as $key ) {
            if ( !array_key_exists( $key, $metas ) ) {
                throw new MetaMissingException( 'No or not defined "' . $key . '" in metas' );
            }

            if ( is_array( $metas[ $key ] ) ) {
                $meta[ $key ] = json_encode( $metas[ $key ], JSON_UNESCAPED_SLASHES );
            }
        }

        // Create & fill atom meta object
        $atomMeta = new AtomMeta( $metas );
        $atomMeta->addPolicy( $policy );

        // Create rule isotope atom
        $this->addAtom( Atom::create(
            'R',
            $this->sourceWallet,
            null,
            $metaType,
            $metaId,
            $atomMeta
        ) );

        // Add continuID atom
        $this->addContinuIdAtom();

        return $this;
    }

    /**
     * Add continuID atom
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function addContinuIdAtom (): Molecule {

        // Creating a remainder wallet if needed
        if ( !$this->remainderWallet || $this->remainderWallet->tokenSlug !== 'USER' ) {
            $this->remainderWallet = new Wallet( $this->secret() );
        }

        $this->addAtom( Atom::create(
            'I',
            $this->remainderWallet,
            null,
            'walletBundle',
            $this->remainderWallet->bundleHash
        ) );

        return $this;
    }

    /**
     * @param int $amount
     * @param array $tokenUnits
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function replenishToken ( int $amount, array $tokenUnits = [] ): Molecule {

        if ( $amount < 0 ) {
            throw new TransferAmountException( 'Number of tokens being replenished must be a positive value.' );
        }

        // Special code for the token unit logic
        if ( $tokenUnits ) {

            // Prepare token units to formatted style
            $tokenUnits = Wallet::getTokenUnits( $tokenUnits );

            // Merge token units with source wallet & new items
            $this->remainderWallet->tokenUnits = array_merge( $this->sourceWallet->tokenUnits, $tokenUnits );
            $this->remainderWallet->balance = count( $this->remainderWallet->tokenUnits );

            // Override first atom's token units to replenish values
            $this->sourceWallet->tokenUnits = $tokenUnits;
            $this->sourceWallet->balance = count( $this->sourceWallet->tokenUnits );
        }

        // Update wallet's balances
        else {
            $this->remainderWallet->balance = $this->sourceWallet->balance + $amount;
            $this->sourceWallet->balance = $amount;
        }

        // Initializing a new Atom to remove tokens from source
        $this->addAtom( Atom::create(
            'V',
            $this->sourceWallet,
            $this->sourceWallet->balance,
        ) );
        $this->addAtom( Atom::create(
            'V',
            $this->remainderWallet,
            $this->remainderWallet->balance,
            'walletBundle',
            $this->remainderWallet->bundleHash,
        ) );

        return $this;
    }

    /**
     * @param array $tokenUnits
     * @param Wallet $recipientWallet
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function fuseToken ( array $tokenUnits, Wallet $recipientWallet ): Molecule {

        // Calculate amount
        $amount = count( $tokenUnits );

        if ( !$this->sourceWallet->hasEnoughBalance( $amount ) ) {
            throw new TransferBalanceException();
        }

        // Initializing a new Atom to remove tokens from source
        $this->addAtom( Atom::create(
            'V',
            $this->sourceWallet,
            -$amount,
        ) );

        // Add F isotope for fused tokens creation
        $this->addAtom( Atom::create(
            'F',
            $recipientWallet,
            1,
            'walletBundle',
            $recipientWallet->bundleHash,
        ) );

        $this->addAtom( Atom::create(
            'V',
            $this->remainderWallet,
            $this->sourceWallet->balance - $amount,
            'walletBundle',
            $this->remainderWallet->bundleHash,
        ) );

        return $this;
    }

    /**
     * @param int $amount
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function burnToken ( int $amount ): Molecule {

        if ( $amount < 0 ) {
            throw new TransferAmountException( 'Number of tokens being burned must be a positive value.' );
        }

        if ( !$this->sourceWallet->hasEnoughBalance( $amount ) ) {
            throw new TransferBalanceException();
        }

        // Initializing a new Atom to remove tokens from source
        $this->addAtom( Atom::create(
            'V',
            $this->sourceWallet,
            -$amount,
        ) );

        $this->addAtom( Atom::create(
            'V',
            $this->remainderWallet,
            $this->sourceWallet->balance - $amount,
            'walletBundle',
            $this->remainderWallet->bundleHash,
        ) );

        return $this;
    }

    /**
     * Initialize a V-type molecule to transfer value from one wallet to another, with a third,
     * regenerated wallet receiving the remainder
     *
     * @param Wallet $recipientWallet
     * @param int $amount
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function initValue ( Wallet $recipientWallet, int $amount ): Molecule {

        if ( !$this->sourceWallet->hasEnoughBalance( $amount ) ) {
            throw new TransferBalanceException();
        }

        // Initializing a new Atom to remove tokens from source
        $this->addAtom( Atom::create(
            'V',
            $this->sourceWallet,
            -$amount,
        ) );

        // Initializing a new Atom to add tokens to recipient
        $this->addAtom( Atom::create(
            'V',
            $recipientWallet,
            $amount,
            'walletBundle',
            $recipientWallet->bundleHash,
        ) );

        // Initializing a new Atom to deposit remainder in a new wallet
        $this->addAtom( Atom::create(
            'V',
            $this->remainderWallet,
            $this->sourceWallet->balance - $amount,
            'walletBundle',
            $this->remainderWallet->bundleHash,
        ) );

        return $this;
    }

    /**
     * @param int $amount
     * @param array $tradeRates
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function initDepositBuffer ( int $amount, array $tradeRates ): Molecule {

        if ( !$this->sourceWallet->hasEnoughBalance( $amount ) ) {
            throw new TransferBalanceException();
        }

        // Create a buffer wallet
        $bufferWallet = Wallet::create( $this->secret, $this->sourceWallet->tokenSlug, $this->sourceWallet->batchId );
        $bufferWallet->tradeRates = $tradeRates;

        // Initializing a new Atom to remove tokens from source
        $this->addAtom( Atom::create(
            'V',
            $this->sourceWallet,
            -$amount,
        ) );

        // Initializing a new Atom to add tokens to recipient
        $this->addAtom( Atom::create(
            'B',
            $bufferWallet,
            $amount,
            'walletBundle',
            $bufferWallet->bundleHash,
        ) );

        // Initializing a new Atom to deposit remainder in a new wallet
        $this->addAtom( Atom::create(
            'V',
            $this->remainderWallet,
            $this->sourceWallet->balance - $amount,
            'walletBundle',
            $this->remainderWallet->bundleHash,
        ) );

        return $this;
    }

    /**
     * Initialize withdraw buffer (BVB molecule OR BV..VB combination)
     *
     * @param array $recipients
     * @param Wallet|null $signingWallet
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function initWithdrawBuffer ( array $recipients, ?Wallet $signingWallet = null ): Molecule {

        // Get the final sum of the recipients amount
        $amount = array_sum( $recipients );

        // Check sender's wallet balance
        if ( !$this->sourceWallet->hasEnoughBalance( $amount ) ) {
            throw new TransferBalanceException();
        }

        // Set a metas signing wallet data for molecule reconciliation ability
        $firstAtomMeta = new AtomMeta();
        if ( $signingWallet ) {
            $firstAtomMeta->addSigningWallet( $signingWallet );
        }

        // Initializing a new Atom to remove tokens from source
        $this->addAtom( Atom::create(
            'B',
            $this->sourceWallet,
            -$amount,
            'walletBundle',
            $this->sourceWallet->bundleHash,
            $firstAtomMeta
        ) );

        // Initializing a new Atom to add tokens to recipient
        foreach ( $recipients as $recipientBundleHash => $recipientAmount ) {
            $this->addAtom( new Atom(
                null,
                null,
                'V',
                $this->sourceWallet->tokenSlug,
                $recipientAmount,
                $this->sourceWallet->batchId ? Crypto::generateBatchId() : null,
                'walletBundle',
                $recipientBundleHash,
            ) );
        }

        // Initializing a new Atom to withdraw remainder in a new wallet
        $this->addAtom( Atom::create(
            'B',
            $this->remainderWallet,
            $this->sourceWallet->balance - $amount,
            'walletBundle',
            $this->remainderWallet->bundleHash,
        ) );

        return $this;
    }

    /**
     * @param Wallet $newWallet
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function initWalletCreation ( Wallet $newWallet ): Molecule {

        $atomMeta = new AtomMeta( [
            'walletAddress' => $newWallet->walletAddress,
            'token' => $newWallet->tokenSlug,
            'bundleHash' => $newWallet->bundleHash,
            'walletPosition' => $newWallet->walletPosition,
            'batch_id' => $newWallet->batchId,
        ] );

        // Create an 'C' atom
        $this->addAtom( Atom::create(
            'C',
            $this->sourceWallet,
            null,
            'wallet',
            $newWallet->walletAddress,
            $atomMeta,
        ) );

        // Add continuID atom
        $this->addContinuIdAtom();

        return $this;
    }

    /**
     * @param string $slug
     * @param string $host
     * @param string|null $peerId
     * @param string|null $name
     * @param array $cellSlugs
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function initPeerCreation ( string $slug, string $host, string $peerId = null, string $name = null, array $cellSlugs = [] ): Molecule {

        // Metas
        $atomMeta = new AtomMeta( [
            'host' => $host,
            'name' => $name,
            'cellSlugs' => json_encode( $cellSlugs ),
            'peerId' => $peerId,
        ] );

        // Create an 'C' atom
        $this->addAtom( Atom::create(
            'C',
            $this->sourceWallet,
            null,
            'peer',
            $slug,
            $atomMeta,
        ) );

        // Add continuID atom
        $this->addContinuIdAtom();

        return $this;
    }

    /**
     * Initialize a C-type molecule to issue a new type of token
     *
     * @param Wallet $recipientWallet - wallet receiving the tokens. Needs to be initialized for the new token beforehand.
     * @param int $amount - how many of the token we are initially issuing (for fungible tokens only)
     * @param array $metas - additional fields to configure the token
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function initTokenCreation ( Wallet $recipientWallet, int $amount, array $metas ): Molecule {

        // Fill metas with wallet property
        foreach ( [
            'walletAddress' => 'walletAddress',
            'walletPosition' => 'walletPosition',
            'walletPubkey' => 'pubkey',
            'walletCharacters' => 'characters',
        ] as $metaKey => $walletProperty ) {
            if ( !array_get( $metas, $metaKey ) ) {
                $meta[ $metaKey ] = $recipientWallet->$walletProperty;
            }
        }

        // The primary atom tells the ledger that a certain amount of the new token is being issued.
        $this->addAtom( Atom::create(
            'C',
            $this->sourceWallet,
            $amount,
            'token',
            $recipientWallet->tokenSlug,
            new AtomMeta( $metas ),
            $recipientWallet->batchId,
        ) );

        // Add continuID atom
        $this->addContinuIdAtom();

        return $this;
    }

    /**
     * Init shadow wallet claim
     *
     * @param string $tokenSlug
     * @param Wallet $wallet
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function initShadowWalletClaim ( string $tokenSlug, Wallet $wallet ): Molecule {

        $atomMeta = new AtomMeta( [
            'tokenSlug' => $tokenSlug,
            'walletAddress' => $wallet->walletAddress,
            'walletPosition' => $wallet->walletPosition,
            'pubkey' => $wallet->pubkey,
            'characters' => $wallet->characters,
            'batchId' => $wallet->batchId,
        ] );

        // Create an 'C' atom
        $this->addAtom( Atom::create(
            'C',
            $this->sourceWallet,
            null,
            'wallet',
            $wallet->walletAddress,
            $atomMeta,
        ) );

        // Add continuID atom
        $this->addContinuIdAtom();

        return $this;
    }

    /**
     * Initialize an M-type molecule with the given data
     *
     * @param array $metas
     * @param string $metaType
     * @param string $metaId
     * @param array $policy
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function initMeta ( array $metas, string $metaType, string $metaId, array $policy = [] ): Molecule {

        $atomMetas = new AtomMeta( $metas );

        if ( count( $policy ) ) {
            $atomMetas->addPolicy( $policy );
        }

        $this->addAtom( Atom::create(
            'M',
            $this->sourceWallet,
            null,
            $metaType,
            $metaId,
            $atomMetas
        ) );

        // Add continuID atom
        $this->addContinuIdAtom();

        return $this;
    }

    /**
     * Initialize meta append molecule
     *
     * @param array $metas
     * @param string $metaType
     * @param string $metaId
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function initMetaAppend ( array $metas, string $metaType, string $metaId ): Molecule {

        $this->addAtom( Atom::create(
            'A',
            $this->sourceWallet,
            null,
            $metaType,
            $metaId,
            new AtomMeta( $metas )
        ) );

        // Add continuID atom
        $this->addContinuIdAtom();

        return $this;

    }

    /**
     * @param string $tokenSlug
     * @param int $amount
     * @param string $recipientBundle
     * @param array $metas
     * @param string|null $batchId
     *
     * @return $this
     * @throws JsonException
     * @throws KnishIOException
     * @throws SodiumException
     */
    public function initTokenRequest ( string $tokenSlug, int $amount, string $recipientBundle, array $metas = [], ?string $batchId = null ): Molecule {

        // Set meta token
        $metas[ 'tokenSlug' ] = $tokenSlug;

        $this->addAtom( Atom::create(
            'T',
            $this->sourceWallet,
            $amount,
            'walletBundle',
            $recipientBundle,
            new AtomMeta( $metas ),
            $batchId,
        ) );

        // Add continuID atom
        $this->addContinuIdAtom();

        return $this;
    }

    /**
     * @param array $metas
     *
     * @return $this
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function initAuthorization ( array $metas = [] ): Molecule {

        $this->addAtom( Atom::create(
            'U',
            $this->sourceWallet,
            null,
            null,
            null,
            new AtomMeta( $metas ),
        ) );

        // Add continuID atom
        $this->addContinuIdAtom();

        return $this;
    }

    /**
     * Creates a one-time signature for a molecule and breaks it up across multiple atoms within that
     * molecule. Resulting 4096 byte (2048 character) string is the one-time signature, which is then compressed.
     *
     * @param bool $anonymous
     * @param bool $compressed
     *
     * @throws KnishIOException
     * @throws JsonException
     */
    public function sign ( bool $anonymous = false, bool $compressed = true ): void {
        if ( empty( $this->atoms ) || !empty( array_filter( $this->atoms, static function ( $atom ) {
                return !( $atom instanceof Atom );
            } ) ) ) {
            throw new MoleculeAtomsMissingException();
        }

        if ( !$anonymous ) {
            $this->bundleHash = Crypto::generateBundleHash( $this->secret );
        }

        $this->atoms = Atom::sortAtoms( $this->atoms );
        $this->molecularHash = Atom::hashAtoms( $this->atoms );

        // Determine first atom
        /** @var Atom $firstAtom */
        $firstAtom = reset( $this->atoms );

        // Set signing position from the first atom
        $signingPosition = $firstAtom->walletPosition;

        // Try to get custom signing position from the metas (local molecule with server secret)
        if ( $signingWallet = array_get( $firstAtom->aggregatedMeta(), 'signingWallet' ) ) {
            $signingPosition = array_get( json_decode( $signingWallet, true ), 'walletPosition' );
        }

        // Signing position is required
        if ( !$signingPosition ) {
            throw new WalletSignatureException();
        }

        // Generate the private signing key for this molecule
        $key = Wallet::generateKey( $this->secret, $firstAtom->tokenSlug, $signingPosition );

        // Building a one-time-signature
        $signatureFragments = $this->signatureFragments( $key );

        // Compressing the OTS
        if ( $compressed ) {
            $signatureFragments = Strings::hexToBase64( $signatureFragments );
        }

        // Chunking the signature across multiple atoms
        $chunkedSignature = Strings::chunkSubstr( $signatureFragments, ceil( mb_strlen( $signatureFragments ) / count( $this->atoms ) ) );
        foreach ( $chunkedSignature as $chunkCount => $chunk ) {
            $this->atoms[ $chunkCount ]->otsFragment = $chunk;
        }
    }

}
