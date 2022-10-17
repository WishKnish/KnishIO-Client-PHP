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
use WishKnish\KnishIO\Client\Exception\TokenSlugFormatException;
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
   * @return string
   */
  public static function continuIdMetaType (): string {
    return 'walletBundle';
  }

  /**
   * @param string $secret
   * @param Wallet|null $sourceWallet
   * @param Wallet|null $remainderWallet
   * @param string|null $cellSlug
   *
   * @throws SodiumException
   */
  public function __construct (
    private string $secret,
    private ?Wallet $sourceWallet,
    private ?Wallet $remainderWallet,
    ?string $cellSlug = null
  ) {
    parent::__construct( $cellSlug );

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
    $this->createdAt = Strings::currentTimeMillis();
    $this->atoms = [];

    return $this;
  }

  /**
   * @param Atom $atom
   *
   * @return $this
   */
  public function addAtom ( Atom $atom ): Molecule {

    // Reset molecular hash
    $this->molecularHash = null;

    // Set atom's index
    $atom->index = $this->generateIndex();

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
   */
  public function addContinuIdAtom (): Molecule {

    $this->addAtom( Atom::create(
      $this->remainderWallet,
      'I',
      null,
      static::continuIdMetaType(),
      $this->remainderWallet->bundle,
    ) );

    return $this;
  }


  /**
   * @param string $metaType
   * @param string $metaId
   * @param array $meta
   *
   * @return $this
   * @throws JsonException
   */
  public function createRule ( string $metaType, string $metaId, array $meta ): Molecule {

    foreach ( [ 'conditions', 'callback', 'rule', ] as $key ) {
      if ( !array_key_exists( $key, $meta ) ) {
        throw new MetaMissingException( 'No or not defined "' . $key . '" in meta' );
      }

      if ( is_array( $meta[ $key ] ) ) {
        $meta[ $key ] = json_encode( $meta[ $key ], JSON_UNESCAPED_SLASHES );
      }
    }

    $this->addAtom( Atom::create(
      $this->sourceWallet,
      'R',
      null,
      $metaType,
      $metaId,
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
      $this->sourceWallet,
      'V',
      $this->sourceWallet->balance,
    ) );
    $this->addAtom( Atom::create(
      $this->remainderWallet,
      'V',
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
   */
  public function fuseToken ( array $tokenUnits, Wallet $recipientWallet ): Molecule {

    // Calculate amount
    $amount = count( $tokenUnits );

    if ( !$this->sourceWallet->hasEnoughBalance( $amount ) ) {
      throw new TransferBalanceException();
    }

    // Initializing a new Atom to remove tokens from source
    $this->addAtom( Atom::create(
      $this->sourceWallet,
      'V',
      -$amount,
    ) );

    // Add F isotope for fused tokens creation
    $this->addAtom( Atom::create(
      $recipientWallet,
      'F',
      1,
      'walletBundle',
      $recipientWallet->bundle,
    ) );

    $this->addAtom( Atom::create(
      $this->remainderWallet,
      'V',
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
      $this->sourceWallet,
      'V',
      -$amount,
    ) );

    $this->addAtom( Atom::create(
      $this->remainderWallet,
      'V',
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
   */
  public function initValue ( Wallet $recipientWallet, int $amount ): Molecule {

    if ( !$this->sourceWallet->hasEnoughBalance( $amount ) ) {
      throw new TransferBalanceException();
    }

    // Initializing a new Atom to remove tokens from source
    $this->addAtom( Atom::create(
      $this->sourceWallet,
      'V',
      -$amount,
    ) );

    // Initializing a new Atom to add tokens to recipient
    $this->addAtom( Atom::create(
      $recipientWallet,
      'V',
      $amount,
      'walletBundle',
      $recipientWallet->bundle,
    ) );

    // Initializing a new Atom to deposit remainder in a new wallet
    $this->addAtom( Atom::create(
      $this->remainderWallet,
      'V',
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
      $this->sourceWallet,
      'V',
      -$amount,
    ) );

    // Initializing a new Atom to add tokens to recipient
    $this->addAtom( Atom::create(
      $bufferWallet,
      'B',
      $amount,
      'walletBundle',
      $bufferWallet->bundle,
    ) );

    // Initializing a new Atom to deposit remainder in a new wallet
    $this->addAtom( Atom::create(
      $this->remainderWallet,
      'V',
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
   */
  public function initWithdrawBuffer ( array $recipients, ?Wallet $signingWallet = null ): Molecule {

    // Get the final sum of the recipients amount
    $amount = array_sum( $recipients );

    // Check sender's wallet balance
    if ( !$this->sourceWallet->hasEnoughBalance( $amount ) ) {
      throw new TransferBalanceException();
    }

    // Set a metas signing wallet data for molecule reconciliation ability
    $firstAtomMeta = new AtomMeta;
    if ( $signingWallet ) {
      $firstAtomMeta->addSigningWallet( $signingWallet );
    }

    // Initializing a new Atom to remove tokens from source
    $this->addAtom( Atom::create(
      $this->sourceWallet,
      'B',
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
      $this->remainderWallet,
      'B',
      $this->sourceWallet->balance - $amount,
      'walletBundle',
      $this->remainderWallet->bundle,
    ) );

    return $this;
  }

  /**
   * @param Wallet $newWallet
   *
   * @return $this
   * @throws JsonException
   */
  public function initWalletCreation ( Wallet $newWallet ): Molecule {

    $atomMeta = new AtomMeta( [
      'address' => $newWallet->address,
      'token' => $newWallet->token,
      'bundle' => $newWallet->bundle,
      'position' => $newWallet->position,
      'amount' => '0',
      'batch_id' => $newWallet->batchId,
    ] );

    // Create an 'C' atom
    $this->addAtom( Atom::create(
      $this->sourceWallet,
      'C',
      null,
      'wallet',
      $newWallet->address,
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
      $this->sourceWallet,
      'C',
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
   * @param array $meta - additional fields to configure the token
   *
   * @param Wallet $recipientWallet
   * @param int $amount
   * @param array $meta
   *
   * @return $this
   * @throws JsonException
   */
  public function initTokenCreation ( Wallet $recipientWallet, int $amount, array $meta ): Molecule {

    // Fill metas with wallet property
    foreach ( [
      'walletAddress' => 'address',
      'walletPosition' => 'position',
      'walletPubkey' => 'pubkey',
      'walletCharacters' => 'characters',
    ] as $metaKey => $walletProperty ) {
      if ( !array_get( $meta, $metaKey ) ) {
        $meta[ $metaKey ] = $recipientWallet->$walletProperty;
      }
    }

    // The primary atom tells the ledger that a certain amount of the new token is being issued.
    $this->addAtom( Atom::create(
      $this->sourceWallet,
      'C',
      $amount,
      'token',
      $recipientWallet->token,
      new AtomMeta( $meta ),
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
   */
  public function initShadowWalletClaim ( string $tokenSlug, Wallet $wallet ): Molecule {

    $atomMeta = new AtomMeta( [
      'tokenSlug' => $tokenSlug,
      'walletAddress' => $wallet->address,
      'walletPosition' => $wallet->position,
      'batchId' => $wallet->batchId,
    ] );

    // Create an 'C' atom
    $this->addAtom( Atom::create(
      $this->sourceWallet,
      'C',
      null,
      'wallet',
      $wallet->address,
      $atomMeta,
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
   */
  public function initIdentifierCreation ( string $type, string $contact, string $code ): Molecule {

    $atomMeta = new AtomMeta( [
      'code' => $code,
      'hash' => Crypto::generateBundleHash( trim( $contact ) ),
    ] );

    // Create an 'C' atom
    $this->addAtom( Atom::create(
      $this->sourceWallet,
      'C',
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
   *
   * @return $this
   * @throws JsonException
   */
  public function initMeta ( array $meta, string $metaType, string $metaId ): Molecule {

    $this->addAtom( Atom::create(
      $this->sourceWallet,
      'M',
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
   * Initialize meta append molecule
   *
   * @param array $meta
   * @param string $metaType
   * @param string $metaId
   *
   * @return $this
   * @throws JsonException
   */
  public function initMetaAppend ( array $meta, string $metaType, string $metaId ): Molecule {

    $this->addAtom( Atom::create(
      $this->sourceWallet,
      'A',
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
   * @param string $metaType
   * @param string $metaId
   * @param array $meta
   * @param string|null $batchId
   *
   * @return $this
   */
  public function initTokenRequest ( string $token, int $amount, string $recipientBundle, array $meta = [], ?string $batchId = null ): Molecule {

    // Set meta token
    $meta[ 'token' ] = $token;

    $this->addAtom( Atom::create(
      $this->sourceWallet,
      'T',
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
   */
  public function initAuthorization ( array $meta = [] ): Molecule {

    $this->addAtom( Atom::create(
      $this->sourceWallet,
      'U',
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

    // Try to get custom signing position from the metas (local molecule with server secret)
    if ( $signingWallet = array_get( $firstAtom->aggregatedMeta(), 'signingWallet' ) ) {
      $signingPosition = array_get( json_decode( $signingWallet, true ), 'position' );
    }

    // Signing position is required
    if ( !$signingPosition ) {
      throw new WalletSignatureException();
    }

    // Generate the private signing key for this molecule
    $key = Wallet::generateWalletKey( $this->secret, $firstAtom->token, $signingPosition );

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

}
