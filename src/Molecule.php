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
 * @property string|null $bundle
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
   */
  public function __construct (
    private readonly string $secret,
    private ?Wallet $sourceWallet = null,
    private ?Wallet $remainderWallet = null,
    ?string $cellSlug = null,
    private ?string $version = null
  ) {
    parent::__construct( $cellSlug );

    // Generates remainder wallet if source wallet is provided
    if ( $remainderWallet || $sourceWallet ) {
      $this->remainderWallet = $remainderWallet ?: Wallet::create( $secret, $sourceWallet->token, $sourceWallet->batchId, $sourceWallet->characters );
    }

    $this->clear();
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
   * @return string
   */
  public function secret (): string {
    return $this->secret;
  }

  /**
   * Source wallet
   */
  public function sourceWallet (): ?Wallet {
    return $this->sourceWallet;
  }

  /**
   * @return Wallet
   */
  public function remainderWallet (): Wallet {
    return $this->remainderWallet;
  }

  /**
   * Clears the instance of the data, leads the instance to a state equivalent to that after new Molecule()
   *
   * @return self
   */
  public function clear (): Molecule {
    $this->molecularHash = null;
    $this->bundle = null;
    $this->status = null;
    // Support deterministic testing with KNISHIO_FIXED_TIMESTAMP environment variable
    $fixedTimestamp = getenv('KNISHIO_FIXED_TIMESTAMP');
    if ($fixedTimestamp !== false) {
      $this->createdAt = strval(intval($fixedTimestamp) * 1000);
    } else {
      $this->createdAt = Strings::currentTimeMillis();
    }
    $this->atoms = [];
    $this->version = null;

    return $this;
  }

  /**
   * @param Atom $atom
   *
   * @return $this
   * @throws SodiumException
   */
  public function addAtom ( Atom $atom ): Molecule {

    // Reset molecular hash
    $this->molecularHash = null;

    // Set atom's index
    $atom->index = $this->generateIndex();
    $atom->version = ($this->version !== null) ? (string) $this->version : null;
    // Add source wallet if not already set when adding first atom
    if ( !$this->sourceWallet && ( count( $this->atoms ) === 0 ) ) {
      $this->sourceWallet = new Wallet( $this->secret(), $atom->token, $atom->position, $atom->batchId );
    }

    // Add atom
    $this->atoms[] = $atom;

    // Sort atoms
    $this->atoms = Atom::sortAtoms( $this->atoms );

    return $this;
  }

  /**
   * Add continuID atom
   *
   * @return $this
   * @throws JsonException
   * @throws SodiumException
   */
  public function addContinuIdAtom (): Molecule {

    // Creating a remainder wallet if needed
    if( !$this->remainderWallet || $this->remainderWallet->token !== 'USER' ) {
      $this->remainderWallet = new Wallet( $this->secret() );
    }

    $this->addAtom( Atom::create(
      'I',
      $this->remainderWallet,
      null,
      'walletBundle',
      $this->remainderWallet->bundle
    ) );

    return $this;
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
   */
  public function addPolicyAtom(
    string $metaType,
    string $metaId,
    array $metas = [],
    array $policy = []
  ): self {

    // AtomMeta object initialization
    $atomMeta = new AtomMeta( $metas );
    $atomMeta->addPolicy( $policy );

    // Create a wallet for the R isotope atom (following JavaScript pattern)
    $wallet = Wallet::create(
      $this->secret,
      'USER',
      null,
      null,
      null,
      $this->sourceWallet->bundle
    );

    $this->addAtom( Atom::create(
      'R',
      $wallet,
      null,
      $metaType,
      $metaId,
      $atomMeta
    ) );

    return $this;
  }

  /**
   * @param string $metaType
   * @param string $metaId
   * @param array $meta
   * @param array $policy
   *
   * @return $this
   * @throws JsonException
   * @throws SodiumException
   */
  public function createRule (
    string $metaType,
    string $metaId,
    array $meta,
    array $policy = []
  ): Molecule {

    foreach ( [
      'conditions',
      'callback',
      'rule'
    ] as $key ) {
      if ( !array_key_exists( $key, $meta ) ) {
        throw new MetaMissingException( 'No or not defined "' . $key . '" in meta' );
      }

      if ( is_array( $meta[ $key ] ) ) {
        $meta[ $key ] = json_encode( $meta[ $key ], JSON_UNESCAPED_SLASHES );
      }
    }

    // Create & fill atom meta object
    $atomMeta = new AtomMeta( $meta );
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
   * @param int $amount
   * @param array $tokenUnits
   *
   * @return $this
   * @throws JsonException
   * @throws SodiumException
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
      $this->remainderWallet->bundle,
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
      $recipientWallet->bundle,
    ) );

    $this->addAtom( Atom::create(
      'V',
      $this->remainderWallet,
      $this->sourceWallet->balance - $amount,
      'walletBundle',
      $this->remainderWallet->bundle,
    ) );

    return $this;
  }

  /**
   * @param int $amount
   *
   * @return $this
   * @throws JsonException
   * @throws SodiumException
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
      $this->remainderWallet->bundle,
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
   */
  public function initValue ( Wallet $recipientWallet, int $amount ): Molecule {

    if ( !$this->sourceWallet->hasEnoughBalance( $amount ) ) {
      throw new TransferBalanceException();
    }

    // Initializing a new Atom to remove entire balance from source (UTXO model)
    $this->addAtom( Atom::create(
      'V',
      $this->sourceWallet,
      -$this->sourceWallet->balance,
    ) );

    // Initializing a new Atom to add tokens to recipient
    $this->addAtom( Atom::create(
      'V',
      $recipientWallet,
      $amount,
      'walletBundle',
      $recipientWallet->bundle,
    ) );

    // Initializing a new Atom to deposit remainder in a new wallet
    $this->addAtom( Atom::create(
      'V',
      $this->remainderWallet,
      $this->sourceWallet->balance - $amount,
      'walletBundle',
      $this->remainderWallet->bundle,
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
   */
  public function initDepositBuffer ( int $amount, array $tradeRates ): Molecule {

    if ( !$this->sourceWallet->hasEnoughBalance( $amount ) ) {
      throw new TransferBalanceException();
    }

    // Create a buffer wallet
    $bufferWallet = Wallet::create( $this->secret, $this->sourceWallet->token, $this->sourceWallet->batchId );
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
      $bufferWallet->bundle,
    ) );

    // Initializing a new Atom to deposit remainder in a new wallet
    $this->addAtom( Atom::create(
      'V',
      $this->remainderWallet,
      $this->sourceWallet->balance - $amount,
      'walletBundle',
      $this->remainderWallet->bundle,
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
      $firstAtomMeta->setSigningWallet( $signingWallet );
    }

    // Initializing a new Atom to remove tokens from source
    $this->addAtom( Atom::create(
      'B',
      $this->sourceWallet,
      -$amount,
      'walletBundle',
      $this->sourceWallet->bundle,
      $firstAtomMeta
    ) );

    // Initializing a new Atom to add tokens to recipient
    foreach ( $recipients as $recipientBundle => $recipientAmount ) {
      $this->addAtom( new Atom(
        null,
        null,
        'V',
        $this->sourceWallet->token,
        $recipientAmount,
        $this->sourceWallet->batchId ? Crypto::generateBatchId() : null,
        'walletBundle',
        $recipientBundle,
      ) );
    }

    // Initializing a new Atom to withdraw remainder in a new wallet
    $this->addAtom( Atom::create(
      'B',
      $this->remainderWallet,
      $this->sourceWallet->balance - $amount,
      'walletBundle',
      $this->remainderWallet->bundle,
    ) );

    return $this;
  }

  /**
   * @param Wallet $wallet
   * @param AtomMeta|null $atomMeta
   *
   * @return $this
   * @throws JsonException
   * @throws SodiumException
   */
  public function initWalletCreation ( Wallet $wallet, AtomMeta $atomMeta = null ): Molecule {

    // Create an atom metadata
    $atomMeta = $atomMeta ?? new AtomMeta;
    $atomMeta->setMetaWallet( $wallet );

    // Create an 'C' atom
    $this->addAtom( Atom::create(
      'C',
      $this->sourceWallet,
      null,
      'wallet',
      $wallet->address,
      $atomMeta,
      $wallet->batchId
    ) );

    // Add continuID atom
    $this->addContinuIdAtom();

    return $this;
  }

  /**
   * @param Wallet $wallet
   *
   * @return $this
   * @throws JsonException
   * @throws SodiumException
   */
  public function initShadowWalletClaim ( Wallet $wallet ): Molecule {
    $atomMeta = ( new AtomMeta )->setShadowWalletClaim( true );
    return $this->initWalletCreation( $wallet, $atomMeta );
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
   */
  public function initTokenCreation ( Wallet $recipientWallet, int $amount, array $metas ): Molecule {

    // Atom meta with new wallet data
    $meta = new AtomMeta( $metas );
    $meta->setMetaWallet( $recipientWallet );

    // The primary atom tells the ledger that a certain amount of the new token is being issued.
    $this->addAtom( Atom::create(
      'C',
      $this->sourceWallet,
      $amount,
      'token',
      $recipientWallet->token,
      $meta,
      $recipientWallet->batchId,
    ) );

    // Add continuID atom
    $this->addContinuIdAtom();

    return $this;
  }

  /**
   * Initialize a C-type molecule to issue a new type of identifier
   *
   * @param string $type
   * @param string $contact
   * @param string $code
   *
   * @return $this
   * @throws JsonException
   * @throws SodiumException
   */
  public function initIdentifierCreation ( string $type, string $contact, string $code ): Molecule {

    $atomMeta = new AtomMeta( [
      'code' => $code,
      'hash' => Crypto::generateBundleHash( trim( $contact ) ),
    ] );

    // Create an 'C' atom
    $this->addAtom( Atom::create(
      'C',
      $this->sourceWallet,
      null,
      'identifier',
      $type,
      $atomMeta,
    ) );

    // Add continuID atom
    $this->addContinuIdAtom();

    return $this;
  }

  /**
   * Initialize an M-type molecule with the given data
   *
   * @param array $meta
   * @param string $metaType
   * @param string $metaId
   * @param array $policy
   *
   * @return $this
   * @throws JsonException
   * @throws SodiumException
   */
  public function initMeta ( array $meta, string $metaType, string $metaId, array $policy = [] ): Molecule {

    $this->addAtom( Atom::create(
      'M',
      $this->sourceWallet,
      null,
      $metaType,
      $metaId,
      new AtomMeta( $meta )
    ) );

    // Only add policy atom if policy is provided and not empty (matches JavaScript SDK)
    if (!empty($policy)) {
      $this->addPolicyAtom( $metaType, $metaId, $meta, $policy );
    }

    // Add continuID atom
    $this->addContinuIdAtom();

    return $this;
  }

  /**
   * Initialize meta append molecule
   *
   * @param array $meta
   * @param string $metaType
   * @param string $metaId
   *
   * @return $this
   * @throws JsonException
   * @throws SodiumException
   */
  public function initMetaAppend ( array $meta, string $metaType, string $metaId ): Molecule {

    // Set molecule as local
    $this->local = 1;

    $this->addAtom( Atom::create(
      'A',
      $this->sourceWallet,
      null,
      $metaType,
      $metaId,
      new AtomMeta( $meta )
    ) );

    // Add continuID atom
    $this->addContinuIdAtom();

    return $this;

  }

  /**
   * @param string $token
   * @param int $amount
   * @param string $recipientBundle
   * @param array $meta
   * @param string|null $batchId
   *
   * @return $this
   * @throws JsonException
   * @throws SodiumException
   */
  public function initTokenRequest ( string $token, int $amount, string $recipientBundle, array $meta = [], ?string $batchId = null ): Molecule {

    // Set molecule as local
    $this->local = 1;

    // Set meta token
    $meta[ 'token' ] = $token;

    $this->addAtom( Atom::create(
      'T',
      $this->sourceWallet,
      $amount,
      'walletBundle',
      $recipientBundle,
      new AtomMeta( $meta ),
      $batchId,
    ) );

    // Add continuID atom
    $this->addContinuIdAtom();

    return $this;
  }

  /**
   * @param array $meta
   *
   * @return $this
   * @throws JsonException
   * @throws SodiumException
   */
  public function initAuthorization ( array $meta = [] ): Molecule {

    $this->addAtom( Atom::create(
      'U',
      $this->sourceWallet,
      null,
      null,
      null,
      new AtomMeta( $meta ),
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
   * @throws SodiumException
   */
  public function sign ( bool $anonymous = false, bool $compressed = true ): void {
    if ( empty( $this->atoms ) || !empty( array_filter( $this->atoms, static function ( $atom ) {
        return !( $atom instanceof Atom );
      } ) ) ) {
      throw new MoleculeAtomsMissingException();
    }

    if ( !$anonymous ) {
      $this->bundle = Crypto::generateBundleHash( $this->secret );
    }

    $this->atoms = Atom::sortAtoms( $this->atoms );
    $this->molecularHash = Atom::hashAtoms( $this->atoms );

    // Determine first atom
    /** @var Atom $firstAtom */
    $firstAtom = reset( $this->atoms );

    // Set signing position from the first atom
    $signingPosition = $firstAtom->position;

    // Try to get other specified signing wallet from the metas & override position
    $signingWallet = $firstAtom->getAtomMeta()->getSigningWallet();
    if ( $signingWallet ) {
      $signingPosition = $signingWallet->position;
    }

    // Signing position is required
    if ( !$signingPosition ) {
      throw new WalletSignatureException();
    }

    // Generate the private signing key for this molecule
    $key = Wallet::generateKey( $this->secret, $firstAtom->token, $signingPosition );

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
   * Convert molecule to JSON for cross-SDK validation
   * Matches JavaScript SDK toJSON method - excludes sensitive fields but keeps wallet data
   *
   * @return string
   */
  public function toJSON(): string {
    $data = [];
    
    // Get public properties via reflection
    $reflection = new \ReflectionClass($this);
    foreach ($reflection->getProperties(\ReflectionProperty::IS_PUBLIC) as $property) {
      $name = $property->getName();
      $data[$name] = $property->getValue($this);
    }
    
    // Explicitly add wallet data for cross-SDK validation (private properties)
    if ($this->sourceWallet) {
      $data['sourceWallet'] = [
        'address' => $this->sourceWallet->address,
        'position' => $this->sourceWallet->position,
        'token' => $this->sourceWallet->token,
        'balance' => $this->sourceWallet->balance
      ];
    }
    
    if ($this->remainderWallet) {
      $data['remainderWallet'] = [
        'address' => $this->remainderWallet->address,
        'position' => $this->remainderWallet->position,
        'token' => $this->remainderWallet->token,
        'balance' => $this->remainderWallet->balance ?? 0
      ];
    }
    
    // Remove PHP-specific fields and sensitive data for compatibility
    $excludeFields = ['secret', 'cellSlugOrigin', 'version', 'counterparty', 'local'];
    foreach ($excludeFields as $field) {
      unset($data[$field]);
    }
    
    return json_encode($data);
  }

  /**
   * Get source wallet for cross-SDK validation
   * @return Wallet|null
   */
  public function getSourceWallet(): ?Wallet {
    return $this->sourceWallet;
  }

  /**
   * Get remainder wallet for cross-SDK validation  
   * @return Wallet|null
   */
  public function getRemainderWallet(): ?Wallet {
    return $this->remainderWallet;
  }

  /**
   * Creates a Molecule instance from JSON data (PHP best practices)
   * 
   * Handles cross-SDK deserialization with robust error handling following
   * JavaScript canonical patterns for perfect cross-platform compatibility.
   *
   * @param string $json JSON string to deserialize
   * @param bool $includeValidationContext Reconstruct sourceWallet/remainderWallet (default: true)
   * @param bool $validateStructure Validate required fields (default: true)
   * @return Molecule Reconstructed molecule instance
   * @throws Exception If JSON is invalid or required fields are missing
   */
  public static function fromJSON(
    string $json, 
    bool $includeValidationContext = true, 
    bool $validateStructure = true
  ): Molecule {
    try {
      // Parse JSON safely
      $data = json_decode($json, true);
      if ($data === null) {
        throw new \Exception("Invalid JSON string");
      }

      // Validate required fields if requested
      if ($validateStructure) {
        if (empty($data['molecularHash']) || !isset($data['atoms']) || !is_array($data['atoms'])) {
          throw new \Exception('Invalid molecule data: missing molecularHash or atoms array');
        }
      }

      // Create minimal molecule instance (never include secret from JSON)
      $molecule = new Molecule(
        secret: '',  // Empty string for security (PHP requires non-null)
        sourceWallet: null,
        remainderWallet: null,
        cellSlug: $data['cellSlug'] ?? null
      );

      // Populate core properties
      $molecule->status = $data['status'] ?? null;
      $molecule->molecularHash = $data['molecularHash'] ?? null;
      $molecule->createdAt = $data['createdAt'] ?? null;
      $molecule->bundle = $data['bundle'] ?? null;

      // Reconstruct atoms array with proper Atom instances
      if (isset($data['atoms']) && is_array($data['atoms'])) {
        $molecule->atoms = [];
        
        foreach ($data['atoms'] as $index => $atomData) {
          try {
            // Create atom with required fields
            $atom = new Atom(
              position: $atomData['position'] ?? '',
              walletAddress: $atomData['walletAddress'] ?? '',
              isotope: $atomData['isotope'] ?? 'V',
              token: $atomData['token'] ?? '',
              value: $atomData['value'] ?? null,
              batchId: $atomData['batchId'] ?? null,
              metaType: $atomData['metaType'] ?? null,
              metaId: $atomData['metaId'] ?? null,
              meta: $atomData['meta'] ?? []
            );
            
            // Set additional properties
            if (isset($atomData['index'])) {
              $atom->index = $atomData['index'];
            }
            if (isset($atomData['otsFragment'])) {
              $atom->otsFragment = $atomData['otsFragment'];
            }
            if (isset($atomData['createdAt'])) {
              $atom->createdAt = $atomData['createdAt'];
            }
            
            $molecule->atoms[] = $atom;
            
          } catch (\Exception $e) {
            throw new \Exception("Failed to reconstruct atom $index: " . $e->getMessage());
          }
        }
      }

      // Reconstruct validation context if available and requested
      if ($includeValidationContext) {
        if (isset($data['sourceWallet']) && $data['sourceWallet']) {
          $swData = $data['sourceWallet'];
          
          // Create source wallet for validation (without secret for security)
          $sourceWallet = new Wallet(
            secret: '',  // Empty string for security (PHP requires non-null)
            token: $swData['token'] ?? 'TEST',
            position: $swData['position'] ?? null
          );
          
          // Set additional properties for validation context
          $sourceWallet->balance = $swData['balance'] ?? 0;
          $sourceWallet->address = $swData['address'] ?? null;
          $sourceWallet->bundle = $swData['bundle'] ?? null;
          
          $molecule->sourceWallet = $sourceWallet;
        }

        if (isset($data['remainderWallet']) && $data['remainderWallet']) {
          $rwData = $data['remainderWallet'];
          
          // Create remainder wallet for validation (without secret for security)
          $remainderWallet = new Wallet(
            secret: '',  // Empty string for security (PHP requires non-null)
            token: $rwData['token'] ?? 'TEST',
            position: $rwData['position'] ?? null
          );
          
          // Set additional properties for validation context
          $remainderWallet->balance = $rwData['balance'] ?? 0;
          $remainderWallet->address = $rwData['address'] ?? null;
          $remainderWallet->bundle = $rwData['bundle'] ?? null;
          
          $molecule->remainderWallet = $remainderWallet;
        }
      }

      return $molecule;

    } catch (\Exception $e) {
      throw new \Exception("Molecule deserialization failed: " . $e->getMessage());
    }
  }

}
