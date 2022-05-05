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
use GuzzleHttp\Exception\GuzzleException;
use WishKnish\KnishIO\Client\Exception\BatchIdException;
use WishKnish\KnishIO\Client\Exception\CodeException;
use WishKnish\KnishIO\Client\Exception\StackableUnitAmountException;
use WishKnish\KnishIO\Client\Exception\StackableUnitDecimalsException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\TransferWalletException;
use WishKnish\KnishIO\Client\Exception\UnauthenticatedException;
use WishKnish\KnishIO\Client\Exception\WalletShadowException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Decimal;
use WishKnish\KnishIO\Client\Mutation\MutationActiveSession;
use WishKnish\KnishIO\Client\Mutation\MutationCreateMeta;
use WishKnish\KnishIO\Client\Mutation\MutationCreateWallet;
use WishKnish\KnishIO\Client\Mutation\MutationRequestAuthorizationGuest;
use WishKnish\KnishIO\Client\Query\Query;
use WishKnish\KnishIO\Client\Query\QueryActiveSession;
use WishKnish\KnishIO\Client\Query\QueryBalance;
use WishKnish\KnishIO\Client\Query\QueryBatch;
use WishKnish\KnishIO\Client\Query\QueryContinuId;
use WishKnish\KnishIO\Client\Mutation\MutationRequestAuthorization;
use WishKnish\KnishIO\Client\Mutation\MutationCreateIdentifier;
use WishKnish\KnishIO\Client\Mutation\MutationProposeMolecule;
use WishKnish\KnishIO\Client\Mutation\MutationCreateToken;
use WishKnish\KnishIO\Client\Mutation\MutationRequestTokens;
use WishKnish\KnishIO\Client\Mutation\MutationTransferTokens;
use WishKnish\KnishIO\Client\Mutation\MutationClaimShadowWallet;
use WishKnish\KnishIO\Client\Query\QueryMetaType;
use WishKnish\KnishIO\Client\Query\QueryToken;
use WishKnish\KnishIO\Client\Query\QueryUserActivity;
use WishKnish\KnishIO\Client\Query\QueryWalletBundle;
use WishKnish\KnishIO\Client\Query\QueryWalletList;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\HttpClient\HttpClient;
use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;
use WishKnish\KnishIO\Client\Response\ResponseContinuId;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;
use WishKnish\KnishIO\Client\Response\ResponseRequestAuthorization;
use WishKnish\KnishIO\Client\Response\ResponseWalletList;



/**
 * Class KnishIO
 * @package WishKnish\KnishIO\Client
 */
class KnishIOClient {

  /**
   * @var HttpClient
   */
  private HttpClient $client;

  /**
   * @var string|null
   */
  private ?string $secret;

  /**
   * @var string|null
   */
  private ?string $bundle;

  /**
   * @var Wallet|null
   */
  private ?Wallet $remainderWallet;

  /**
   * @var Query
   */
  private Query $lastMoleculeQuery;

  /**
   * @var string|null
   */
  private ?string $cellSlug = null;

  /**
   * @var int
   */
  private int $serverSdkVersion;

  /**
   * @var array
   */
  private array $uris = [];

  /**
   * @var array
   */
  private array $authTokenObjects = [];

  /**
   * @var AuthToken|null
   */
  private ?AuthToken $authToken;

  /**
   * @param Wallet $sourceWallet
   * @param array|null $sendTokenUnits
   *
   * @return array
   */
  #[Pure]
  public static function splitTokenUnits ( Wallet $sourceWallet, ?array $sendTokenUnits ): array {

    // Not a token units transfer
    if ( $sendTokenUnits === null ) {
      return [ 0, [], [] ];
    }

    // Calculate amount
    $amount = count( $sendTokenUnits );

    // Init recipient & remainder token units
    $recipientTokenUnits = [];
    $remainderTokenUnits = [];
    foreach ( $sourceWallet->tokenUnits as $tokenUnit ) {
      if ( in_array( $tokenUnit->id, $sendTokenUnits, true ) ) {
        $recipientTokenUnits[] = $tokenUnit;
      }
      else {
        $remainderTokenUnits[] = $tokenUnit;
      }
    }

    return [ $amount, $recipientTokenUnits, $remainderTokenUnits, ];
  }

  /**
   * KnishIOClient constructor.
   *
   * @param string|array $uri
   * @param HttpClientInterface|null $client
   * @param int $serverSdkVersion
   *
   * @throws Exception
   */
  public function __construct ( string|array $uri, HttpClientInterface $client = null, int $serverSdkVersion = 3 ) {
    $this->initialize( $uri, $client, $serverSdkVersion );
  }

  /**
   * @param string|array $uri
   * @param HttpClientInterface|null $client
   * @param int $serverSdkVersion
   */
  public function initialize ( string|array $uri, HttpClientInterface $client = null, int $serverSdkVersion = 3 ): void {
    $this->reset();

    // Init uris
    $this->uris = is_array( $uri ) ? $uri : [ $uri ];
    foreach( $this->uris as $uri ) {
      $this->authTokenObjects[ $uri ] = null; // @todo remove this code if it is not required!
    }

    $this->client = $client ?? new HttpClient( $this->getRandomUri() );
    $this->serverSdkVersion = $serverSdkVersion;
  }

  /**
   * @param bool $encrypt
   *
   * @return bool
   */
  public function switchEncryption ( bool $encrypt ): bool {
    if ( $this->hasEncryption() === $encrypt ) {
      return false;
    }

    if ( $encrypt ) {
      $this->enableEncryption();
    }
    else {
      $this->disableEncryption();
    }
    return true;
  }

  /**
   * Get random uri from specified $this->uris
   *
   * @return string
   * @throws Exception
   */
  public function getRandomUri (): string {
    return $this->uris[ random_int(0, count( $this->uris ) - 1) ];
  }

  /**
   * Reset common properties
   */
  public function reset (): void {
    $this->secret = null;
    $this->bundle = null;
    $this->remainderWallet = null;
  }

  /**
   * @return string|null
   */
  public function cellSlug (): ?string {
    return $this->cellSlug;
  }

  /**
   * @param string|null $cellSlug
   */
  public function setCellSlug ( ?string $cellSlug ): void {
    $this->cellSlug = $cellSlug;
  }

  /**
   * @return string
   */
  public function uri (): string {
    return $this->client->getUri();
  }

  /**
   * @return HttpClient
   * @todo rename to HttpClient!
   */
  public function client (): HttpClient {
    return $this->client;
  }

  /**
   * Has a secret?
   */
  public function hasSecret (): bool {
    return (bool) $this->secret;
  }

  /**
   * @param string $secret
   *
   * @throws Exception
   */
  public function setSecret ( string $secret ): void {
    $this->secret = $secret;
    $this->bundle = Crypto::generateBundleHash( $secret );
  }

  /**
   * @return bool
   */
  public function hasEncryption (): bool {
    return $this->client()
      ->hasEncryption();
  }

  public function enableEncryption (): void {
    $this->client()
      ->enableEncryption();
  }

  public function disableEncryption (): void {
    $this->client()
      ->disableEncryption();
  }

  /**
   * @return string|null
   */
  public function getSecret (): ?string {
    if ( !$this->secret ) {
      throw new UnauthenticatedException( 'KnishIOClient::getSecret - Expected ' . static::class . '::authentication call before.' );
    }

    return $this->secret;
  }

  /**
   * Returns the bundle hash for this session
   *
   * @returns {string}
   */
  public function getBundle (): ?string {
    if ( !$this->bundle ) {
      throw new UnauthenticatedException( 'KnishIOClient::getBundle() - Unable to find a stored bundle!' );
    }
    return $this->bundle;
  }

  /**
   * @return Wallet|null
   */
  public function getRemainderWallet (): ?Wallet {
    return $this->remainderWallet;
  }

  /**
   * @param string|null $secret
   * @param Wallet|null $sourceWallet
   * @param Wallet|null $remainderWallet
   *
   * @return Molecule
   * @throws Exception|GuzzleException
   */
  public function createMolecule ( string $secret = null, Wallet $sourceWallet = null, Wallet $remainderWallet = null ): Molecule {

    $secret = $secret ?: $this->getSecret();

    // Is source wallet passed & has a last success query? Update a source wallet with a remainder one
    if ( $sourceWallet === null &&
      $this->remainderWallet &&
      $this->remainderWallet->token !== 'AUTH' &&
      $this->lastMoleculeQuery
    ) {

      /**
       * @var ResponseMolecule $response
       */
      $response = $this->lastMoleculeQuery->response();

      if ( $response && $response->success() ) {
        $sourceWallet = $this->remainderWallet;
      }
    }

    // Get source wallet by ContinuID query
    if ( $sourceWallet === null ) {
      $sourceWallet = $this->getSourceWallet();
    }

    // Remainder wallet
    $this->remainderWallet = $remainderWallet ?: Wallet::create( $secret, 'USER', $sourceWallet->batchId, $sourceWallet->characters );

    return new Molecule( $secret, $sourceWallet, $this->remainderWallet, $this->cellSlug );
  }

  /**
   * @param string $class
   *
   * @return Query
   */
  public function createQuery ( string $class ): Query {
    return new $class( $this->client );
  }

  /**
   * @param string $class
   * @param Molecule|null $molecule
   *
   * @return MutationProposeMolecule
   * @throws Exception|GuzzleException
   */
  public function createMoleculeMutation ( string $class, Molecule $molecule = null ): MutationProposeMolecule {

    // Init molecule
    $molecule = $molecule ?: $this->createMolecule();

    // Create base query
    $query = new $class ( $this->client, $molecule );

    // Only instances of MutationProposeMolecule supported
    if ( !$query instanceof MutationProposeMolecule ) {
      throw new CodeException( static::class . '::createMoleculeMutation - required class instance of MutationProposeMolecule.' );
    }

    // Save the last molecule query
    $this->lastMoleculeQuery = $query;

    return $query;
  }

  /**
   * @param string $tokenSlug
   * @param string|null $bundleHash
   *
   * @return Response
   * @throws GuzzleException
   * @throws JsonException
   */
  public function queryBalance ( string $tokenSlug, string $bundleHash = null ): Response {

    // Create a query
    /** @var QueryBalance $query */
    $query = $this->createQuery( QueryBalance::class );

    // Execute the query
    return $query->execute( [ 'bundleHash' => $bundleHash ?: $this->getBundle(), 'token' => $tokenSlug, ] );
  }

  /**
   * @param array|string $metaType
   * @param array|string|null $metaId
   * @param array|string|null $key
   * @param array|string|null $value
   * @param bool $latest
   * @param array|null $fields
   *
   * @return array|null
   * @throws GuzzleException
   * @throws JsonException
   */
  public function queryMeta ( array|string $metaType, array|string $metaId = null, array|string $key = null, array|string $value = null, bool $latest = false, array $fields = null ): ?array {

    // Create a query
    /** @var QueryMetaType $query */
    $query = $this->createQuery( QueryMetaType::class );
    $variables = QueryMetaType::createVariables( $metaType, $metaId, $key, $value, $latest );

    // Execute the query
    return $query->execute( $variables, $fields )
      ->payload();
  }

  /**
   * @param string $batchId
   *
   * @return Response
   * @throws Exception|GuzzleException
   */
  public function queryBatch ( string $batchId ): Response {

    $query = $this->createQuery( QueryBatch::class );

    // Execute the query
    return $query->execute( [ 'batchId' => $batchId ] );
  }

  /**
   * @param string $tokenSlug
   *
   * @return Response
   * @throws GuzzleException|Exception
   */
  public function createWallet ( string $tokenSlug ): Response {
    $newWallet = new Wallet( $this->getSecret(), $tokenSlug );

    /**
     * @var MutationCreateWallet $query
     */
    $query = $this->createMoleculeMutation( MutationCreateWallet::class );
    $query->fillMolecule( $newWallet );

    // Execute the query
    return $query->execute();
  }


  /**
   * Queries the ledger to retrieve a list of active sessions for the given MetaType
   *
   * @param {string} bundleHash
   * @param {string} metaType
   * @param {string} metaId
   * @return {Promise<*>}
   */
  public function queryActiveSession ( string $bundleHash, string $metaType, string $metaId ): Response {

    $query = $this->createQuery( QueryActiveSession::class );

    // Execute the query
    return $query->execute( [
      'bundleHash' => $bundleHash,
      'metaType' => $metaType,
      'metaId' => $metaId,
    ] );
  }


  /**
   * Builds and executes a molecule to declare an active session for the given MetaType
   *
   * @param {string} bundle
   * @param {string} metaType
   * @param {string} metaId
   * @param {string} ipAddress
   * @param {string} browser
   * @param {string} osCpu
   * @param {string} resolution
   * @param {string} timeZone
   * @param {object|array} json
   * @return {Promise<void>}
   */
  public function activeSession (
    string $bundle,
    string $metaType,
    string $metaId,
    string $ipAddress,
    string $browser,
    string $osCpu,
    string $resolution,
    string $timeZone,
    array $json = []
  ) {

    $query = $this->createQuery( MutationActiveSession::class );

    // Execute the query
    return $query->execute( [
      'bundleHash' => $bundle,
      'metaType' => $metaType,
      'metaId' => $metaId,
      'ipAddress' => $ipAddress,
      'browser' => $browser,
      'osCpu' => $osCpu,
      'resolution' => $resolution,
      'timeZone' => $timeZone,
      'json' => json_encode( $json )
    ] );
  }


  /**
   *
   * @param {string} bundleHash
   * @param {string} metaType
   * @param {string} metaId
   * @param {string} ipAddress
   * @param {string} browser
   * @param {string} osCpu
   * @param {string} resolution
   * @param {string} timeZone
   * @param {Array} countBy
   * @param {string} interval
   * @return {Promise<*>}
   */
  public function queryUserActivity (
    string $bundleHash,
    string $metaType,
    string $metaId,
    string $ipAddress,
    string $browser,
    string $osCpu,
    string $resolution,
    string $timeZone,
    array $countBy,
    string $interval
  ) {
    $query = $this->createQuery( QueryUserActivity::class );

    return $query->execute( [
      'bundleHash' => $bundleHash,
      'metaType' => $metaType,
      'metaId' => $metaId,
      'ipAddress' => $ipAddress,
      'browser' => $browser,
      'osCpu' => $osCpu,
      'resolution' => $resolution,
      'timeZone' => $timeZone,
      'countBy' => $countBy,
      'interval' => $interval,
    ] );
  }

  /**
   * @param string $token
   * @param float $amount
   * @param array $meta
   * @param string|null $batchId
   * @param array $units
   *
   * @return Response
   * @throws GuzzleException
   * @throws JsonException
   */
  public function createToken ( string $token, float $amount, array $meta = [], ?string $batchId = null, array $units = [] ): Response {
    if ( array_get( $meta, 'fungibility' ) === 'stackable' ) { // For stackable token - create a batch ID

      // Generate batch ID if it does not pass
      $batchId = $batchId ?? Crypto::generateBatchId();

      // Special logic for token unit initialization
      if ( count( $units ) > 0 ) {

        if ( array_key_exists( 'decimals', $meta ) && $meta[ 'decimals' ] > 0 ) {
          throw new StackableUnitDecimalsException();
        }

        if ( $amount > 0 ) {
          throw new StackableUnitAmountException();
        }

        $amount = count( $units );

        // Set custom default metadata
        $meta = array_merge( $meta, [ 'splittable' => 1, 'decimals' => 0, 'tokenUnits' => json_encode( $units ), ] );
      }
    }

    // Set default decimals value
    if ( !array_has( $meta, 'decimals' ) ) {
      $meta[ 'decimals' ] = 0;
    }

    // Recipient wallet
    $recipientWallet = new Wallet( $this->getSecret(), $token, null, $batchId );

    // Create a query
    /** @var MutationCreateToken $query */
    $query = $this->createMoleculeMutation( MutationCreateToken::class );

    // Init a molecule
    $query->fillMolecule( $recipientWallet, $amount, $meta );

    // Return a query execution result
    return $query->execute();
  }

  /**
   * @param string $metaType
   * @param string $metaId
   * @param array $metadata
   *
   * @return Response
   * @throws GuzzleException|JsonException
   */
  public function createMeta ( string $metaType, string $metaId, array $metadata = [] ): Response {

    // Create a custom molecule
    $molecule = $this->createMolecule( $this->getSecret(), $this->getSourceWallet() );

    // Create & execute a query
    /** @var MutationCreateMeta $query */
    $query = $this->createMoleculeMutation( MutationCreateMeta::class, $molecule );

    // Init a molecule
    $query->fillMolecule( $metaType, $metaId, $metadata );

    // Execute a query
    return $query->execute();
  }

  /**
   * @param string $type
   * @param string $contact
   * @param string $code
   *
   * @return Response
   * @throws GuzzleException
   * @throws JsonException
   */
  public function createIdentifier ( string $type, string $contact, string $code ): Response {

    // Create & execute a query
    /** @var MutationCreateIdentifier $query */
    $query = $this->createMoleculeMutation( MutationCreateIdentifier::class );

    // Init a molecule
    $query->fillMolecule( $type, $contact, $code );

    // Execute a query
    return $query->execute();
  }

  /**
   * @param string|null $bundleHash
   * @param string|null $token
   * @param bool $unspent
   *
   * @return array|null
   * @throws GuzzleException|Exception
   */
  public function queryWallets ( ?string $bundleHash = null, ?string $token = null, bool $unspent = true ): ?array {

    /**
     * @var QueryWalletList $query
     */
    $query = $this->createQuery( QueryWalletList::class );

    /**
     * @var ResponseWalletList $response
     */
    $response = $query->execute( [ 'bundleHash' => $bundleHash ?: $this->getBundle(), 'token' => $token, 'unspent' => $unspent, ] );

    return $response->getWallets();
  }

  /**
   * @param string $tokenSlug
   * @param string|null $bundleHash
   *
   * @return array|null
   * @throws GuzzleException|Exception
   */
  public function queryShadowWallets ( string $tokenSlug = 'KNISH', string $bundleHash = null ): ?array {
    return $this->queryWallets( $bundleHash, $tokenSlug );
  }

  /**
   * @param string|null $bundleHash
   * @param string|null $key
   * @param string|null $value
   * @param bool $latest
   * @param array|null $fields
   *
   * @return Response
   * @throws GuzzleException|JsonException
   */
  public function queryBundle ( string $bundleHash = null, string $key = null, string $value = null, bool $latest = true, array $fields = null ): Response {
    /**
     * Create a query
     *
     * @var QueryWalletBundle $query
     */
    $query = $this->createQuery( QueryWalletBundle::class );
    $variables = QueryWalletBundle::createVariables( $bundleHash, $key, $value, $latest );

    // Execute the query
    return $query->execute( $variables, $fields );
  }

  /**
   * @param string $token
   * @param float $amount
   * @param Wallet|string|null $to
   * @param array $meta
   * @param string|null $batchId
   * @param array $units
   *
   * @return Response
   * @throws GuzzleException
   * @throws JsonException
   */
  public function requestTokens ( string $token, float $amount, Wallet|string $to = null, array $meta = [], ?string $batchId = null, array $units = [] ): Response {

    // Get a token & init is Stackable flag for batch ID initialization
    $tokenResponse = $this->createQuery( QueryToken::class )
      ->execute( [ 'slug' => $token ] );
    $isStackable = array_get( $tokenResponse->data(), '0.fungibility' ) === 'stackable';

    // NON-stackable tokens & batch ID is NOT NULL - error
    if ( !$isStackable && $batchId !== null ) {
      throw new BatchIdException( 'Expected Batch ID = null for non-stackable tokens.' );
    }
    // Stackable tokens & batch ID is NULL - generate new one
    if ( $isStackable && $batchId === null ) {
      $batchId = Crypto::generateBatchId();
    }

    if ( count( $units ) > 0 ) {
      if ( $amount > 0 ) {
        throw new StackableUnitAmountException();
      }

      $amount = count( $units );
      $meta[ 'tokenUnits' ] = json_encode( $units );
    }

    if ( $to !== null ) {

      // Is a string? $to is bundle or secret
      if ( is_string( $to ) ) {

        // Bundle: set metaType
        if ( Wallet::isBundleHash( $to ) ) {
          $metaType = 'walletBundle';
          $metaId = $to;
        } // Secret: create a new wallet (not shadow)
        else {
          $to = Wallet::create( $to, $token );
        }
      }

      // Is a wallet object?
      if ( $to instanceof Wallet ) {

        // Meta type: wallet
        $metaType = 'wallet';

        // Set wallet metas
        $meta = array_merge( $meta, [ 'position' => $to->position, 'bundle' => $to->bundle, ] );

        // Set metaId as an wallet address
        $metaId = $to->address;
      }
    }
    else {
      $metaType = 'walletBundle';
      $metaId = $this->getBundle();
    }

    // Create a query
    /** @var MutationRequestTokens $query */
    $query = $this->createMoleculeMutation( MutationRequestTokens::class );

    // Init a molecule
    $query->fillMolecule( $token, $amount, $metaType, $metaId, $meta, $batchId );

    // Return a query execution result
    return $query->execute();
  }

  /**
   * Claim a shadow wallet
   *
   * @param string $token
   * @param string|null $batchId
   * @param null $molecule
   *
   * @return Response
   * @throws Exception|GuzzleException
   */
  public function claimShadowWallet ( string $token, ?string $batchId = null, $molecule = null ): Response {
    /**
     * Create a query
     * @var MutationClaimShadowWallet $query
     */
    $query = $this->createMoleculeMutation( MutationClaimShadowWallet::class, $molecule );
    $query->fillMolecule( $token, $batchId );

    // Return a response
    return $query->execute();
  }

  /**
   * @param string $token
   *
   * @return array
   * @throws Exception|GuzzleException
   */
  public function claimShadowWallets ( string $token ): array {
    // Get shadow wallet list
    $shadowWallets = $this->queryShadowWallets( $token );

    // Check shadow wallets
    if ( !$shadowWallets ) {
      throw new WalletShadowException();
    }
    foreach ( $shadowWallets as $shadowWallet ) {
      if ( !$shadowWallet->isShadow() ) {
        throw new WalletShadowException();
      }
    }

    // Claim shadow wallet list
    $responses = [];
    foreach ( $shadowWallets as $shadowWallet ) {
      $responses[] = $this->claimShadowWallet( $token, $shadowWallet->batchId );
    }
    return $responses;
  }

  /**
   * @param Wallet|string $recipient
   * @param string $token
   * @param float $amount
   * @param string|null $batchId
   * @param array $units
   * @param Wallet|null $sourceWallet
   *
   * @return Response
   * @throws GuzzleException
   * @throws JsonException
   */
  public function transferToken ( Wallet|string $recipient, string $token, float $amount = 0, ?string $batchId = null, array $units = [], ?Wallet $sourceWallet = null ): Response {

    // Get a from wallet
    /** @var Wallet|null $fromWallet */
    $fromWallet = $sourceWallet ?? $this->queryBalance( $token, $this->getBundle() )
        ->payload();

    // Calculate amount & set meta key
    if ( count( $units ) > 0 ) {
      // Can't move stackable units AND provide amount
      if ( $amount > 0 ) {
        throw new StackableUnitAmountException();
      }

      $amount = count( $units );
    }

    if ( $fromWallet === null || Decimal::cmp( $fromWallet->balance, $amount ) < 0 ) {
      throw new TransferBalanceException( 'The transfer amount cannot be greater than the sender\'s balance' );
    }

    $recipientWallet = $recipient;

    if ( !$recipientWallet instanceof Wallet ) {

      // Get final bundle hash
      $bundleHash = Wallet::isBundleHash( $recipient ) ? $recipient : Crypto::generateBundleHash( $recipient );

      // try to get a valid wallet
      $recipientWallet = $this->queryBalance( $token, $bundleHash )
        ->payload();

      // Has not wallet yet - create it
      if ( $recipientWallet === null ) {
        $recipientWallet = Wallet::create( $recipient, $token );
      }
    }


    // Compute the batch ID for the recipient
    // (typically used by stackable tokens)
    if ( $batchId !== null ) {
      $recipientWallet->batchId = $batchId;
    }
    else {
      $recipientWallet->initBatchId( $fromWallet );
    }

    // Remainder wallet
    $this->remainderWallet = Wallet::create( $this->getSecret(), $token, $fromWallet->batchId, $fromWallet->characters );
    $this->remainderWallet->initBatchId( $fromWallet, true );

    $fromWallet->splitUnits( $units, $this->remainderWallet, $recipientWallet );

    // Create a molecule with custom source wallet
    $molecule = $this->createMolecule( null, $fromWallet, $this->remainderWallet );

    // Create a query
    /** @var MutationTransferTokens $query */
    $query = $this->createMoleculeMutation( MutationTransferTokens::class, $molecule );

    // Init a molecule
    $query->fillMolecule( $recipientWallet, $amount );

    // Execute a query
    return $query->execute();
  }

  /**
   * @param string $token
   * @param float $amount
   * @param array $units
   * @param Wallet|null $sourceWallet
   *
   * @return Response
   * @throws GuzzleException
   * @throws JsonException
   */
  public function burnToken ( string $token, float $amount, array $units = [], ?Wallet $sourceWallet = null ): Response {

    // Get a from wallet
    /** @var Wallet|null $fromWallet */
    $fromWallet = $sourceWallet ?? $this->queryBalance( $token, $this->getBundle() )
        ->payload();

    if ( $fromWallet === null ) {
      throw new TransferWalletException( 'Source wallet is missing or invalid.' );
    }

    // Remainder wallet
    $remainderWallet = Wallet::create( $this->getSecret(), $token, $fromWallet->batchId, $fromWallet->characters );
    $remainderWallet->initBatchId( $fromWallet, true );

    // Calculate amount & set meta key
    if ( count( $units ) > 0 ) {

      // Can't burn stackable units AND provide amount
      if ( $amount > 0 ) {
        throw new StackableUnitAmountException();
      }

      // Calculating amount based on Unit IDs
      $amount = count( $units );

      // --- Token units splitting
      $fromWallet->splitUnits( $units, $remainderWallet );
    }

    // Burn tokens
    $molecule = $this->createMolecule( null, $fromWallet, $remainderWallet );
    $molecule->burnToken( $amount );
    $molecule->sign();
    $molecule->check();

    return ( new MutationProposeMolecule( $this->client(), $molecule ) )->execute();

  }

  /**
   * @param string $tokenSlug
   * @param float $amount
   * @param array $tokenUnits
   * @param Wallet|null $sourceWallet
   *
   * @return Response
   * @throws GuzzleException
   * @throws JsonException
   */
  public function replenishToken ( string $tokenSlug, float $amount, array $tokenUnits = [], ?Wallet $sourceWallet = null ): Response {

    // Get a from wallet
    /** @var Wallet|null $fromWallet */
    $fromWallet = $sourceWallet ?? $this->queryBalance( $tokenSlug )
        ->payload();
    if ( $fromWallet === null ) {
      throw new TransferWalletException( 'Source wallet is missing or invalid.' );
    }

    // Remainder wallet
    $remainderWallet = Wallet::create( $this->getSecret(), $tokenSlug, $fromWallet->batchId, $fromWallet->characters );
    $remainderWallet->initBatchId( $fromWallet, true );

    // Burn tokens
    $molecule = $this->createMolecule( null, $fromWallet, $remainderWallet );
    $molecule->replenishToken( $amount, $tokenUnits );
    $molecule->sign();
    $molecule->check();

    return ( new MutationProposeMolecule( $this->client(), $molecule ) )
      ->execute();
  }

  /**
   * @param Wallet|string $recipient
   * @param string $tokenSlug
   * @param array $fusedTokenUnits
   * @param Wallet|null $sourceWallet
   *
   * @return Response
   * @throws GuzzleException
   * @throws JsonException
   */
  public function fuseToken( Wallet|string $recipient, string $tokenSlug, array $fusedTokenUnitIds, ?Wallet $sourceWallet = null  ) {

    // Get a from wallet
    /** @var Wallet|null $fromWallet */
    $fromWallet = $sourceWallet ?? $this->queryBalance( $tokenSlug )
        ->payload();
    if ( $fromWallet === null ) {
      throw new TransferWalletException( 'Source wallet is missing or invalid.' );
    }
    if ( !$fromWallet->tokenUnits ) {
      throw new TransferWalletException( 'Source wallet does not have token units.' );
    }
    if ( !$fusedTokenUnitIds ) {
      throw new TransferWalletException( 'Fused token unit list is empty.' );
    }

    // Check fused token units
    $sourceTokenUnitIds = [];
    foreach( $fromWallet->tokenUnits as $tokenUnit ) {
      $sourceTokenUnitIds[] = $tokenUnit->id;
    }
    foreach( $fusedTokenUnitIds as $fusedTokenUnitId ) {
      if ( !in_array( $fusedTokenUnitId, $sourceTokenUnitIds ) ) {
        throw new TransferWalletException( 'Fused token unit ID = "' . $fusedTokenUnitId . '" does not found in the source wallet.' );
      }
    }

    // Generate new recipient wallet
    $recipientWallet = $recipient;
    if ( is_string( $recipient ) ) {
      $recipientWallet = Wallet::create( $recipient, $tokenSlug );
    }
    // Set batch ID
    $recipientWallet->initBatchId( $fromWallet );

    // Remainder wallet
    $remainderWallet = Wallet::create( $this->getSecret(), $tokenSlug, $fromWallet->batchId, $fromWallet->characters );
    $remainderWallet->initBatchId( $fromWallet, true );


    // Split token units (fused)
    $fromWallet->splitUnits( $fusedTokenUnitIds, $remainderWallet );

    // Set recipient fused token units
    $recipientWallet->fusedTokenUnits = $fromWallet->tokenUnits;

    // Create a molecule
    $molecule = $this->createMolecule( null, $fromWallet, $remainderWallet );
    $molecule->fuseToken( $fromWallet->tokenUnits, $recipientWallet );
    $molecule->sign();
    $molecule->check();

    return ( new MutationProposeMolecule( $this->client(), $molecule ) )
      ->execute();
  }

  /**
   * @return Wallet
   * @throws JsonException|GuzzleException
   */
  public function getSourceWallet (): Wallet {
    // Has a ContinuID wallet?
    $sourceWallet = $this->queryContinuId( $this->getBundle() )
      ->payload();
    if ( !$sourceWallet ) {
      $sourceWallet = new Wallet( $this->getSecret() );
    }

    // Return final source wallet
    return $sourceWallet;
  }

  /**
   * @param string $bundleHash
   *
   * @return Response
   * @throws GuzzleException
   * @throws JsonException
   */
  public function queryContinuId ( string $bundleHash ): Response {
    /**
     * Create & execute the query
     *
     * @var QueryContinuId $query
     */
    $query = $this->createQuery( QueryContinuId::class );

    return $query->execute( [ 'bundle' => $bundleHash ] );
  }

  /**
   * @param string $cellSlug
   * @param bool $encrypt
   *
   * @return Response
   * @throws GuzzleException|Exception
   */
  public function requestGuestAuthToken ( string $cellSlug, bool $encrypt ): Response {
    $this->setCellSlug( $cellSlug );

    $query = $this->createQuery( MutationRequestAuthorizationGuest::class );

    $wallet = new Wallet( Libraries\Crypto::generateSecret(), 'AUTH' );

    $response = $query->execute( [
      'cellSlug' => $cellSlug,
      'pubkey' => $wallet->pubkey,
      'encrypt' => $encrypt,
    ] );

    // Create & set an auth token object if there any data in payload (@todo add a key based check?)
    if ( $response->payload() ) {
      $authToken = AuthToken::create( $response->payload(), $wallet, $encrypt );
      $this->setAuthToken( $authToken );
    }

    return $response;
  }

  /**
   * @param string $secret
   * @param bool $encrypt
   *
   * @return Response
   * @throws GuzzleException|JsonException|Exception
   */
  public function requestProfileAuthToken ( string $secret, bool $encrypt ): Response {
    $this->setSecret( $secret );

    $wallet = new Wallet( $secret, 'AUTH' );

    // Create an auth molecule
    $molecule = $this->createMolecule( $secret, $wallet );

    /**
     * Create query & fill a molecule
     *
     * @var MutationRequestAuthorization $query
     */
    $query = $this->createMoleculeMutation( MutationRequestAuthorization::class, $molecule );
    $query->fillMolecule( [ 'encrypt' => $encrypt ? 'true' : 'false' ] );

    /**
     * Get a response
     *
     * @var ResponseRequestAuthorization $response
     */
    $response = $query->execute();

    // Create & set an auth token object if the response is successful
    if ( $response->success() ) {
      $authToken = AuthToken::create( $response->payload(), $wallet, $encrypt );
      $this->setAuthToken( $authToken );
    }

    return $response;
  }

  /**
   * @param string|null $secret
   * @param string|null $cellSlug
   * @param bool $encrypt
   *
   * @return Response
   * @throws GuzzleException
   * @throws JsonException
   */
  public function requestAuthToken( ?string $secret, string $cellSlug = null, bool $encrypt = false ): Response {

    // Response for request guest/profile auth token
    $response = null;

    // Set a cell slug
    $this->setCellSlug( $cellSlug );

    // Authorized user
    if ( $secret ) {
      $response = $this->requestProfileAuthToken( $secret, $encrypt );
    }

    // Guest
    else {
      $response = $this->requestGuestAuthToken( $cellSlug, $encrypt );
    }

    // Switch encryption
    $this->switchEncryption( $encrypt );

    // Return full response
    return $response;
  }

  /**
   * Sets an auth token
   *
   * @param AuthToken|null $authToken
   */
  public function setAuthToken ( ?AuthToken $authToken ): void {

    // An empty auth token
    if ( !$authToken ) {
      return;
    }

    // Save auth token object to global list
    $this->authTokenObjects[ $this->uri() ] = $authToken;

    // Set auth data to apollo client
    $this->client()->setAuthData( $authToken->getToken(), $authToken->getPubkey(), $authToken->getWallet() );

    // Save a full auth token object with expireAt key
    $this->authToken = $authToken;
  }

  /**
   * Returns the current authorization token
   *
   * @return AuthToken|null
   */
  public function getAuthToken (): ?AuthToken {
    return $this->authToken;
  }


}
