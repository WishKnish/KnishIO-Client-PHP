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

use Exception;
use JetBrains\PhpStorm\Pure;
use JsonException;
use ReflectionException;
use WishKnish\KnishIO\Client\Exception\BalanceInsufficientException;
use WishKnish\KnishIO\Client\Exception\MetaMissingException;
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
 * @property string $createdAt
 * @property array $atoms
 */
class Molecule extends MoleculeStructure {
  // @todo move this consts to the config
  private const DEFAULT_META_CONTEXT = 'https://www.schema.org';

  private string $secret;
  private ?Wallet $sourceWallet;
  private Wallet $remainderWallet;

  /**
   * @return string
   */
  public static function continuIdMetaType (): string {
    return 'walletBundle';
  }

  /**
   * Molecule constructor.
   *
   * @param string $secret
   * @param Wallet|null $sourceWallet
   * @param Wallet|null $remainderWallet
   * @param string|null $cellSlug
   *
   * @throws Exception
   */
  public function __construct ( string $secret, ?Wallet $sourceWallet = null, ?Wallet $remainderWallet = null, ?string $cellSlug = null ) {
    parent::__construct( $cellSlug );

    $this->secret = $secret;
    $this->sourceWallet = $sourceWallet;

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
   * Encrypt message by source wallet
   *
   * @param array $data @todo mixed? it must be a strict type!
   * @param array $shared_wallets
   *
   * @return array
   * @throws ReflectionException
   */
  public function encryptMessage ( array $data, array $shared_wallets = [] ): array {
    // Merge all args to the common list
    $pubkeys = [];
    foreach ( $shared_wallets as $shared_wallet ) {
      $pubkeys[] = $shared_wallet->pubkey;
    }

    // Call Wallet::encryptMyMessage function
    return $this->sourceWallet->encryptMyMessage( $data, $this->sourceWallet->pubkey, ...$pubkeys );
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

    $this->molecularHash = null;
    $this->atoms[] = $atom;
    $this->atoms = Atom::sortAtoms( $this->atoms );

    return $this;
  }

  /**
   * @param array $metas
   * @param Wallet|null $wallet
   *
   * @return array
   * @throws JsonException
   */
  protected function finalMetas ( array $metas = [], Wallet $wallet = null ): array {
    $wallet = $wallet ?: $this->sourceWallet;

    $metas[ 'pubkey' ] = $wallet->pubkey;
    $metas[ 'characters' ] = $wallet->characters;

    return $metas;
  }

  /**
   * @param array $metas
   * @param null $context
   *
   * @return array
   */
  protected function contextMetas ( array $metas = [], $context = null ): array {
    // Add context key if it is enabled
    if ( $context ) {
      $metas[ 'context' ] = $context;
    }
    return $metas;
  }

  /**
   * @param Wallet $wallet
   * @param array $metas
   *
   * @return array
   */
  protected function tokenUnitMetas( Wallet $wallet, array $metas = [] ): array {
    // Add token units meta key
    if ( $wallet->hasTokenUnits() ) {
      $metas[ 'tokenUnits' ] = $wallet->tokenUnitsJson();
    }
    return $metas;
  }


  /**
   * @param array $metas
   *
   * @return array
   */
  #[Pure]
  protected function schemaOrgMetas ( array $metas = [] ): array {
    return $this->contextMetas( $metas, static::DEFAULT_META_CONTEXT );
  }

  /**
   * Add user remainder atom
   *
   * @param Wallet $userRemainderWallet
   *
   * @return self
   * @throws JsonException
   */
  public function addUserRemainderAtom ( Wallet $userRemainderWallet ): Molecule {
    $this->molecularHash = null;

    // Metas
    $metas = $this->finalMetas( [], $userRemainderWallet );

    // Add token unit metas if it exists in remainder wallet
    $this->tokenUnitMetas( $userRemainderWallet, $metas );


    // Remainder atom
    $this->atoms[] = new Atom(
      $userRemainderWallet->position,
      $userRemainderWallet->address,
      'I',
      'USER',
      null,
      null,
      static::continuIdMetaType(),
      $userRemainderWallet->bundle,
      $metas,
      null,
      $this->generateIndex()
    );

    $this->atoms = Atom::sortAtoms( $this->atoms );

    return $this;
  }

  public function createRule ( $metaType, $metaId, $meta ): Molecule {

    $aggregateMeta = Meta::aggregateMeta( Meta::normalizeMeta( $meta ) );

    foreach ( [ 'conditions', 'callback', 'rule', ] as $k ) {
      if ( !array_key_exists( $k, $meta ) ) {
        throw new MetaMissingException( 'No or not defined "' . $k . '" in meta' );
      }

      if ( is_array( $aggregateMeta[ $k ] ) ) {
        $aggregateMeta[ $k ] = json_encode( $aggregateMeta[ $k ], JSON_UNESCAPED_SLASHES );
      }
    }

    $this->addAtom( new Atom( $this->sourceWallet->position, $this->sourceWallet->address, 'R', $this->sourceWallet->token, null, null, $metaType, $metaId, $this->finalMetas( $aggregateMeta, $this->sourceWallet ), null, $this->generateIndex() ) );

    // User remainder atom
    $this->addUserRemainderAtom( $this->remainderWallet );
    $this->atoms = Atom::sortAtoms( $this->atoms );

    return $this;

  }

  /**
   * @param float|int $amount
   * @param string $token
   * @param array $metas
   *
   * @return $this
   * @throws JsonException
   */
  public function replenishTokens ( float|int $amount, string $token, array $metas ): Molecule {

    $aggregateMeta = Meta::aggregateMeta( Meta::normalizeMeta( $metas ) );
    $aggregateMeta[ 'action' ] = 'add';

    foreach ( [ 'address', 'position', 'batchId' ] as $key ) {
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
      'USER',
      $amount,
      $this->sourceWallet->batchId,
      'token',
      $token,
      $this->finalMetas( $this->contextMetas( $aggregateMeta ) ),
      null,
      $this->generateIndex()
    );

    // User remainder atom
    $this->addUserRemainderAtom( $this->remainderWallet );
    $this->atoms = Atom::sortAtoms( $this->atoms );

    return $this;
  }

  /**
   * @param float|int $amount
   * @param string|null $walletBundle
   *
   * @return $this
   * @throws BalanceInsufficientException|JsonException
   */
  public function burnToken ( float|int $amount, string $walletBundle = null ): Molecule {

    if ( $amount < 0.0 ) {
      throw new NegativeMeaningException( 'It is impossible to use a negative value for the number of tokens' );
    }

    if ( Decimal::cmp( 0.0, $this->sourceWallet->balance - $amount ) > 0 ) {
      throw new BalanceInsufficientException();
    }

    $this->molecularHash = null;

    // Initializing a new Atom to remove tokens from source
    $this->atoms[] = new Atom(
      $this->sourceWallet->position,
      $this->sourceWallet->address,
      'V',
      $this->sourceWallet->token,
      -$amount,
      $this->sourceWallet->batchId,
      null,
      null,
      $this->finalMetas( $this->tokenUnitMetas( $this->sourceWallet ) ),
      null,
      $this->generateIndex()
    );

    $this->atoms[] = new Atom(
      $this->remainderWallet->position,
      $this->remainderWallet->address,
      'V',
      $this->sourceWallet->token,
      $this->sourceWallet->balance - $amount,
      $this->remainderWallet->batchId,
      $walletBundle ? 'walletBundle' : null,
      $walletBundle,
      $this->finalMetas( $this->tokenUnitMetas( $this->remainderWallet ), $this->remainderWallet ),
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
   * @param float|int $value
   *
   * @return $this
   * @throws BalanceInsufficientException|JsonException
   */
  public function initValue ( Wallet $recipientWallet, float|int $value ): Molecule {

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
      $this->finalMetas( $this->tokenUnitMetas( $this->sourceWallet ) ),
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
      $this->finalMetas( $this->tokenUnitMetas( $recipientWallet ), $recipientWallet ),
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
      $this->finalMetas( $this->tokenUnitMetas( $this->remainderWallet ), $this->remainderWallet ),
      null,
      $this->generateIndex()
    );

    $this->atoms = Atom::sortAtoms( $this->atoms );

    return $this;
  }

  /**
   * @param Wallet $newWallet
   *
   * @return $this
   * @throws JsonException
   */
  public function initWalletCreation ( Wallet $newWallet ): Molecule {
    $this->molecularHash = null;

    // Metas
    $metas = [ 'address' => $newWallet->address, 'token' => $newWallet->token, 'bundle' => $newWallet->bundle, 'position' => $newWallet->position, 'amount' => '0', 'batch_id' => $newWallet->batchId, ];

    // Create an 'C' atom
    $this->atoms[] = new Atom(
      $this->sourceWallet->position,
      $this->sourceWallet->address,
      'C',
      'USER',
      null,
      $this->sourceWallet->batchId,
      'wallet',
      $newWallet->address,
      $this->finalMetas( $this->contextMetas( $metas ), $newWallet ),
      null,
      $this->generateIndex()
    );

    // User remainder atom
    $this->addUserRemainderAtom( $this->remainderWallet );
    $this->atoms = Atom::sortAtoms( $this->atoms );

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
    $this->molecularHash = null;

    // Metas
    $metas = [
      'host' => $host,
      'name' => $name,
      'cellSlugs' => json_encode( $cellSlugs ),
      'peerId' => $peerId,
    ];

    // Create an 'C' atom
    $this->atoms[] = new Atom(
      $this->sourceWallet->position,
      $this->sourceWallet->address,
      'P',
      $this->sourceWallet->token,
      null,
      $this->sourceWallet->batchId,
      'peer',
      $slug,
      $this->finalMetas( $metas ),
      null,
      $this->generateIndex()
    );

    // User remainder atom
    $this->addUserRemainderAtom( $this->remainderWallet );

    $this->atoms = Atom::sortAtoms( $this->atoms );

    return $this;
  }

  /**
   * Initialize a C-type molecule to issue a new type of token
   *
   * @param Wallet $recipientWallet - wallet receiving the tokens. Needs to be initialized for the new token beforehand.
   * @param float|integer $amount - how many of the token we are initially issuing (for fungible tokens only)
   * @param array $meta - additional fields to configure the token
   *
   * @return self
   */
  public function initTokenCreation ( Wallet $recipientWallet, float|int $amount, array $meta ): Molecule {

    $this->molecularHash = null;

    foreach ( [ 'walletAddress', 'walletPosition', 'walletPubkey', 'walletCharacters' ] as $walletKey ) {

      $has = array_filter( $meta, static function ( $token ) use ( $walletKey ) {
        return is_array( $token ) && array_key_exists( 'key', $token ) && $walletKey === $token[ 'key' ];
      } );

      if ( empty( $has ) && !array_key_exists( $walletKey, $meta ) ) {
        $meta[ $walletKey ] = $recipientWallet->{strtolower( substr( $walletKey, 6 ) )};
      }
    }

    // The primary atom tells the ledger that a certain amount of the new token is being issued.
    $this->atoms[] = new Atom(
      $this->sourceWallet->position,
      $this->sourceWallet->address,
      'C',
      'USER',
      $amount,
      $recipientWallet->batchId,
      'token',
      $recipientWallet->token,
      $this->finalMetas( $this->contextMetas( $meta ), $this->sourceWallet ),
      null,
      $this->generateIndex()
    );

    // User remainder atom
    $this->addUserRemainderAtom( $this->remainderWallet );
    $this->atoms = Atom::sortAtoms( $this->atoms );

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

    $this->molecularHash = null;

    $metas = [ 'tokenSlug' => $tokenSlug, 'walletAddress' => $wallet->address, 'walletPosition' => $wallet->position, 'batchId' => $wallet->batchId, ];

    // Create an 'C' atom
    $this->atoms[] = new Atom(
      $this->sourceWallet->position,
      $this->sourceWallet->address,
      'C',
      'USER',
      null,
      $wallet->batchId,
      'wallet',
      $wallet->address,
      $this->finalMetas( $this->contextMetas( $metas ) ),
      null,
      $this->generateIndex()
    );

    // Add user remainder atom
    $this->addUserRemainderAtom( $this->remainderWallet );
    $this->atoms = Atom::sortAtoms( $this->atoms );

    return $this;
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
  public function initIdentifierCreation ( string $type, string $contact, string $code ): Molecule {
    $this->molecularHash = null;

    $metas = [ 'code' => $code, 'hash' => Crypto::generateBundleHash( trim( $contact ) ), ];

    $this->atoms[] = new Atom(
      $this->sourceWallet->position,
      $this->sourceWallet->address,
      'C',
      'USER',
      null,
      null,
      'identifier',
      $type,
      $this->finalMetas( $this->contextMetas( $metas ), $this->sourceWallet ),
      null,
      $this->generateIndex()
    );

    // User remainder atom
    $this->addUserRemainderAtom( $this->remainderWallet );
    $this->atoms = Atom::sortAtoms( $this->atoms );

    return $this;
  }

  /**
   * Initialize an M-type molecule with the given data
   *
   * @param array $meta
   * @param string $metaType
   * @param string $metaId
   *
   * @return self
   */
  public function initMeta ( array $meta, string $metaType, string $metaId ): Molecule {
    $this->molecularHash = null;

    $this->atoms[] = new Atom(
      $this->sourceWallet->position,
      $this->sourceWallet->address,
      'M',
      'USER',
      null,
      $this->sourceWallet->batchId,
      $metaType,
      $metaId,
      $this->finalMetas( $meta, $this->sourceWallet ),
      null,
      $this->generateIndex()
    );

    // User remainder atom
    $this->addUserRemainderAtom( $this->remainderWallet );
    $this->atoms = Atom::sortAtoms( $this->atoms );

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
    $this->molecularHash = null;

    $this->atoms[] = new Atom( $this->sourceWallet->position, $this->sourceWallet->address, 'A', $this->sourceWallet->token, null, null, $metaType, $metaId, $this->finalMetas( $meta ), null, $this->generateIndex() );

    // User remainder atom
    $this->addUserRemainderAtom( $this->remainderWallet );

    $this->atoms = Atom::sortAtoms( $this->atoms );

    return $this;

  }

  /**
   * @param string $token
   * @param float|int $amount
   * @param string $metaType
   * @param string $metaId
   * @param array $meta
   * @param string|null $batchId
   *
   * @return $this
   * @throws JsonException
   */
  public function initTokenRequest ( string $token, float|int $amount, string $metaType, string $metaId, array $meta = [], ?string $batchId = null ): Molecule {

    $this->molecularHash = null;

    // Set meta token
    $meta[ 'token' ] = $token;

    $this->atoms[] = new Atom(
      $this->sourceWallet->position,
      $this->sourceWallet->address,
      'T',
      'USER',
      $amount,
      $batchId,
      $metaType,
      $metaId,
      $this->finalMetas( $meta, $this->sourceWallet ),
      null,
      $this->generateIndex()
    );

    // User remainder atom
    $this->addUserRemainderAtom( $this->remainderWallet );
    $this->atoms = Atom::sortAtoms( $this->atoms );

    return $this;
  }

  /**
   * @param array $meta
   *
   * @return $this
   */
  public function initAuthorization ( array $meta = [] ): Molecule {
    $this->molecularHash = null;

    $this->atoms[] = new Atom( $this->sourceWallet->position, $this->sourceWallet->address, 'U', $this->sourceWallet->token, null, $this->sourceWallet->batchId, null, null, $this->finalMetas( $meta ), null, $this->generateIndex() );

    // User remainder atom
    $this->addUserRemainderAtom( $this->remainderWallet );

    $this->atoms = Atom::sortAtoms( $this->atoms );

    return $this;
  }

  /**
   * Creates a one-time signature for a molecule and breaks it up across multiple atoms within that
   * molecule. Resulting 4096 byte (2048 character) string is the one-time signature, which is then compressed.
   *
   * @param bool $anonymous
   * @param bool $compressed
   *
   * @return string|null
   * @throws Exception|ReflectionException|AtomsMissingException
   */
  public function sign ( bool $anonymous = false, bool $compressed = true ): ?string {
    if ( empty( $this->atoms ) || !empty( array_filter( $this->atoms, static function ( $atom ) {
        return !( $atom instanceof Atom );
      } ) ) ) {
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
  public function generateIndex (): int {
    return static::generateNextAtomIndex( $this->atoms );
  }

  /**
   * @param array $atoms
   *
   * @return int
   */
  public static function generateNextAtomIndex ( array $atoms = [] ): int {

    $atom = end( $atoms );

    return ( false === $atom ) ? 0 : $atom->index + 1;
  }

}
