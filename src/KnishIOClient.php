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
use GuzzleHttp\Exception\GuzzleException;
use JsonException;
use SodiumException;
use WishKnish\KnishIO\Client\Exception\CryptoException;
use WishKnish\KnishIO\Client\Exception\KnishIOException;
use WishKnish\KnishIO\Client\Exception\MoleculeMutationClassException;
use WishKnish\KnishIO\Client\Exception\TokenUnitAmountException;
use WishKnish\KnishIO\Client\Exception\TokenUnitDecimalsException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\TransferBundleException;
use WishKnish\KnishIO\Client\Exception\TransferWalletException;
use WishKnish\KnishIO\Client\Exception\UnauthenticatedException;
use WishKnish\KnishIO\Client\Exception\WalletBatchException;
use WishKnish\KnishIO\Client\Exception\WalletShadowException;
use WishKnish\KnishIO\Client\HttpClient\HttpClient;
use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Mutation\MutationClaimShadowWallet;
use WishKnish\KnishIO\Client\Mutation\MutationCreateMeta;
use WishKnish\KnishIO\Client\Mutation\MutationCreateToken;
use WishKnish\KnishIO\Client\Mutation\MutationCreateWallet;
use WishKnish\KnishIO\Client\Mutation\MutationDepositBufferToken;
use WishKnish\KnishIO\Client\Mutation\MutationProposeMolecule;
use WishKnish\KnishIO\Client\Mutation\MutationRequestAuthorization;
use WishKnish\KnishIO\Client\Mutation\MutationRequestTokens;
use WishKnish\KnishIO\Client\Mutation\MutationTransferTokens;
use WishKnish\KnishIO\Client\Mutation\MutationWithdrawBufferToken;
use WishKnish\KnishIO\Client\Query\Query;
use WishKnish\KnishIO\Client\Query\QueryBalance;
use WishKnish\KnishIO\Client\Query\QueryBatch;
use WishKnish\KnishIO\Client\Query\QueryContinuId;
use WishKnish\KnishIO\Client\Query\QueryMetaType;
use WishKnish\KnishIO\Client\Query\QueryToken;
use WishKnish\KnishIO\Client\Query\QueryWalletBundle;
use WishKnish\KnishIO\Client\Query\QueryWallets;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseBalance;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;
use WishKnish\KnishIO\Client\Response\ResponseRequestAuthorization;
use WishKnish\KnishIO\Client\Response\ResponseWallets;

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
    private ?string $bundleHash;

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
     * @var array
     */
    private array $uris = [];

    /**
     * @var AuthToken|null
     */
    private ?AuthToken $authToken = null;

    /**
     * @var bool
     */
    private bool $encrypt = false;

    /**
     * KnishIOClient constructor.
     *
     * @param string|array $uri
     * @param HttpClientInterface|null $client
     *
     * @throws KnishIOException
     */
    public function __construct ( string|array $uri, HttpClientInterface $client = null ) {
        $this->initialize( $uri, $client );
    }

    /**
     * @param string|array $uri
     * @param HttpClientInterface|null $client
     *
     * @return void
     * @throws KnishIOException
     */
    public function initialize ( string|array $uri, HttpClientInterface $client = null ): void {
        $this->reset();

        // Init uris
        $this->uris = is_array( $uri ) ? $uri : [ $uri ];

        $this->client = $client ?? new HttpClient( $this->getRandomUri() );
    }

    /**
     * @param bool $encrypt
     *
     * @return bool
     */
    public function switchEncryption ( bool $encrypt = false ): bool {
        if ( $this->encrypt === $encrypt ) {
            return false;
        }

        // Set encryption
        $this->encrypt = $encrypt;
        $this->client()
            ->setEncryption( $encrypt );

        return true;
    }

    /**
     * Get random uri from specified $this->uris
     *
     * @return string
     * @throws KnishIOException
     */
    public function getRandomUri (): string {
        try {
            return $this->uris[ random_int( 0, count( $this->uris ) - 1 ) ];
        }
        catch ( Exception $e ) {
            throw new CryptoException( $e->getMessage(), null, $e->getCode(), $e );
        }
    }

    /**
     * Reset common properties
     */
    public function reset (): void {
        $this->secret = null;
        $this->bundleHash = null;
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
     * @return void
     * @throws KnishIOException
     */
    public function setSecret ( string $secret ): void {
        $this->secret = $secret;
        $this->bundleHash = Crypto::generateBundleHash( $secret );
    }

    /**
     * @return string|null
     * @throws KnishIOException
     */
    public function getSecret (): ?string {
        if ( !$this->secret ) {
            throw new UnauthenticatedException();
        }

        return $this->secret;
    }

    /**
     * Returns the bundle hash for this session
     *
     * @returns {string}
     * @throws KnishIOException
     */
    public function getBundleHash (): ?string {
        if ( !$this->bundleHash ) {
            throw new UnauthenticatedException();
        }
        return $this->bundleHash;
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
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function createMolecule ( string $secret = null, Wallet $sourceWallet = null, Wallet $remainderWallet = null ): Molecule {

        $secret = $secret ?: $this->getSecret();

        // Is source wallet passed & has a last success query? Update a source wallet with a remainder one
        if (
            $sourceWallet === null &&
            $this->remainderWallet &&
            $this->remainderWallet->tokenSlug === 'USER' &&
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
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function createMoleculeMutation ( string $class, Molecule $molecule = null ): MutationProposeMolecule {

        // Init molecule
        $molecule = $molecule ?: $this->createMolecule();

        // Create base query
        $query = new $class ( $this->client, $molecule );

        // Only instances of MutationProposeMolecule supported
        if ( !$query instanceof MutationProposeMolecule ) {
            throw new MoleculeMutationClassException( 'Attempting to create a Mutation using class not inherited from MutationProposeMolecule.', $class );
        }

        // Save the last molecule query
        $this->lastMoleculeQuery = $query;

        return $query;
    }

    /**
     * @param string $tokenSlug
     * @param string|null $bundleHash
     * @param string $type
     *
     * @return ResponseBalance
     * @throws GuzzleException
     * @throws JsonException
     * @throws KnishIOException
     */
    public function queryBalance ( string $tokenSlug, string $bundleHash = null, string $type = 'regular' ): ResponseBalance {

        // Create a query
        /** @var QueryBalance $query */
        $query = $this->createQuery( QueryBalance::class );

        // Execute the query
        return $query->execute( [
            'bundleHash' => $bundleHash ?: $this->getBundleHash(),
            'tokenSlug' => $tokenSlug,
            'type' => $type
        ] );
    }

    /**
     * @param string $tokenSlug
     * @param int $amount
     * @param string $type
     *
     * @return Wallet
     * @throws GuzzleException
     * @throws JsonException
     * @throws KnishIOException
     */
    public function querySourceWallet ( string $tokenSlug, int $amount, string $type = 'regular' ): Wallet {

        // Get a from wallet
        /** @var Wallet|null $fromWallet */
        $fromWallet = $this->queryBalance( $tokenSlug, $this->getBundleHash(), $type )
            ->getPayload();

        // Check source wallet balance
        if ( !$fromWallet || !$fromWallet->hasEnoughBalance( $amount ) ) {
            throw new TransferBalanceException('Insufficient balance to complete transfer.', $fromWallet->balance );
        }

        // Check shadow wallet
        if ( !$fromWallet->walletPosition || !$fromWallet->walletAddress ) {
            throw new WalletShadowException( 'Source wallet can not be a shadow wallet.' );
        }

        return $fromWallet;
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
     * @throws KnishIOException
     */
    public function queryMeta ( array|string $metaType, array|string $metaId = null, array|string $key = null, array|string $value = null, bool $latest = false, array $fields = null ): ?array {

        // Create a query
        /** @var QueryMetaType $query */
        $query = $this->createQuery( QueryMetaType::class );
        $variables = QueryMetaType::createVariables( $metaType, $metaId, $key, $value, $latest );

        // Execute the query
        return $query->execute( $variables, $fields )
            ->getPayload();
    }

    /**
     * @param string $batchId
     *
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     * @throws KnishIOException
     */
    public function queryBatch ( string $batchId ): Response {

        // Execute the query
        return $this->createQuery( QueryBatch::class )
            ->execute( [
                'batchId' => $batchId
            ] );
    }

    /**
     * @param string $tokenSlug
     *
     * @return Response
     * @throws GuzzleException
     * @throws SodiumException
     * @throws KnishIOException
     * @throws JsonException
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
     * @param string $tokenSlug
     * @param int $amount
     * @param array $metas
     * @param string|null $batchId
     * @param array $units
     *
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function createToken ( string $tokenSlug, int $amount, array $metas = [], ?string $batchId = null, array $units = [] ): Response {

        $fungibility = array_get( $metas, 'fungibility' );

        // For stackable token - create a batch ID
        if ( $fungibility === 'stackable' ) {
            $batchId = $batchId ?? Crypto::generateBatchId();
        }

        // Special logic for token unit initialization (nonfungible || stackable)
        if ( in_array( $fungibility, [ 'nonfungible', 'stackable' ] ) && count( $units ) > 0 ) {

            if ( array_key_exists( 'decimals', $metas ) && $metas[ 'decimals' ] > 0 ) {
                throw new TokenUnitDecimalsException();
            }

            if ( $amount > 0 ) {
                throw new TokenUnitAmountException();
            }

            $amount = count( $units );

            // Set custom default metadata
            $metas = array_merge( $metas, [
                'splittable' => 1,
                'decimals' => 0,
                'tokenUnits' => json_encode( $units )
            ] );
        }

        // Set default decimals value
        if ( !array_has( $metas, 'decimals' ) ) {
            $metas[ 'decimals' ] = 0;
        }

        // Recipient wallet
        $recipientWallet = new Wallet( $this->getSecret(), $tokenSlug, null, $batchId );

        // Create a query
        /** @var MutationCreateToken $query */
        $query = $this->createMoleculeMutation( MutationCreateToken::class );

        // Init a molecule
        $query->fillMolecule( $recipientWallet, $amount, $metas );

        // Return a query execution result
        return $query->execute();
    }

    /**
     * @param string $metaType
     * @param string $metaId
     * @param array $metas
     *
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     * @throws KnishIOException
     * @throws SodiumException
     */
    public function createMeta ( string $metaType, string $metaId, array $metas = [] ): Response {

        // Create a custom molecule
        $molecule = $this->createMolecule( $this->getSecret(), $this->getSourceWallet() );

        // Create & execute a query
        /** @var MutationCreateMeta $query */
        $query = $this->createMoleculeMutation( MutationCreateMeta::class, $molecule );

        // Init a molecule
        $query->fillMolecule( $metaType, $metaId, $metas );

        // Execute a query
        return $query->execute();
    }

    /**
     * @param string $metaType
     * @param string $metaId
     * @param array $policy
     *
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function createPolicy ( string $metaType, string $metaId, array $policy = [] ): Response {

        // Create a molecule
        $molecule = $this->createMolecule();
        $molecule->addPolicyAtom( $metaType, $metaId, [], $policy );
        $molecule->addContinuIdAtom();
        $molecule->sign();
        $molecule->check();

        // Create & execute a mutation
        $query = $this->createMoleculeMutation( MutationProposeMolecule::class, $molecule );
        return $query->execute();
    }

  /**
   * @param string|null $bundleHash
   * @param string|null $tokenSlug
   * @param bool $unspent
   *
   * @return ResponseWallets
   * @throws GuzzleException
   * @throws JsonException
   * @throws KnishIOException
   */
    public function queryWallets ( ?string $bundleHash = null, ?string $tokenSlug = null, bool $unspent = true ): ResponseWallets {

        /**
         * @var QueryWallets $query
         */
        $query = $this->createQuery( QueryWallets::class );
        return $query->execute( [
            'bundleHash' => $bundleHash ?: $this->getBundleHash(),
            'tokenSlug' => $tokenSlug,
            'unspent' => $unspent
        ] );
    }

    /**
     * @param string $tokenSlug
     * @param string|null $bundleHash
     *
     * @return ResponseWallets
     * @throws GuzzleException
     * @throws JsonException
     * @throws KnishIOException
     */
    public function queryShadowWallets ( string $tokenSlug = 'KNISH', string $bundleHash = null ): ResponseWallets {
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
     * @throws GuzzleException
     * @throws JsonException
     * @throws KnishIOException
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
     * @param string $tokenSlug
     * @param int $amount
     * @param string|null $recipientBundleHash
     * @param array $metas
     * @param string|null $batchId
     * @param array $units
     *
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     * @throws KnishIOException
     * @throws SodiumException
     */
    public function requestTokens ( string $tokenSlug, int $amount, string $recipientBundleHash = null, array $metas = [], ?string $batchId = null, array $units = [] ): Response {

        // No bundle? Use our own
        $recipientBundleHash = $recipientBundleHash ?? $this->getBundleHash();

        // Check recipient bundle hash
        if ( !Crypto::isBundleHash( $recipientBundleHash ) ) {
            throw new TransferBundleException();
        }

        // Get a token & init is Stackable flag for batch ID initialization
        $tokenResponse = $this->createQuery( QueryToken::class )
            ->execute( [ 'tokenSlug' => $tokenSlug ] );
        $isStackable = array_get( $tokenResponse->data(), '0.fungibility' ) === 'stackable';

        // NON-stackable tokens & batch ID is NOT NULL - error
        if ( !$isStackable && $batchId !== null ) {
            throw new WalletBatchException( 'Non-stackable tokens should not have a Batch ID.' );
        }
        // Stackable tokens & batch ID is NULL - generate new one
        if ( $isStackable && $batchId === null ) {
            $batchId = Crypto::generateBatchId();
        }

        // Are we requesting units rather than amounts?
        if ( count( $units ) > 0 ) {

            // Can't specify units and amount simultaneously
            if ( $amount > 0 ) {
                throw new TokenUnitAmountException();
            }

            // Amount will equal number of units being requested
            $amount = count( $units );

            // Specify specific units to request
            $metas[ 'tokenUnits' ] = json_encode( $units );
        }

        // Create a query
        /** @var MutationRequestTokens $query */
        $query = $this->createMoleculeMutation( MutationRequestTokens::class );

        // Init a molecule
        $query->fillMolecule( $tokenSlug, $amount, $recipientBundleHash, $metas, $batchId );

        // Return a query execution result
        return $query->execute();
    }

    /**
     * Claim a shadow wallet
     *
     * @param string $tokenSlug
     * @param string|null $batchId
     * @param $molecule
     *
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function claimShadowWallet ( string $tokenSlug, ?string $batchId = null, $molecule = null ): Response {
        /**
         * Create a query
         * @var MutationClaimShadowWallet $query
         */
        $query = $this->createMoleculeMutation( MutationClaimShadowWallet::class, $molecule );
        $query->fillMolecule( $tokenSlug, $batchId );

        // Return a response
        return $query->execute();
    }

    /**
     * @param string $tokenSlug
     *
     * @return array
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function claimShadowWallets ( string $tokenSlug ): array {
        // Get shadow wallet list
        $wallets = $this->queryShadowWallets( $tokenSlug );

        // Check shadow wallets
        $shadowWallets = [];
        foreach ( $wallets as $wallet ) {
            if ( $wallet->isShadow() ) {
                $shadowWallets[] = $wallet;
            }
        }
        if ( !$shadowWallets ) {
            throw new WalletShadowException();
        }

        // Claim shadow wallet list
        $responses = [];
        foreach ( $shadowWallets as $shadowWallet ) {
            $responses[] = $this->claimShadowWallet( $tokenSlug, $shadowWallet->batchId );
        }
        return $responses;
    }

    /**
     * @param string $bundleHash
     * @param string $tokenSlug
     * @param int $amount
     * @param string|null $batchId
     * @param array $units
     * @param Wallet|null $sourceWallet
     *
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function transferToken ( string $bundleHash, string $tokenSlug, int $amount = 0, ?string $batchId = null, array $units = [], ?Wallet $sourceWallet = null ): Response {

        // Check bundle hash is secret has passed
        if ( !Crypto::isBundleHash( $bundleHash ) ) {
            throw new TransferBundleException();
        }

        // Calculate amount & set meta key
        if ( count( $units ) > 0 ) {

            // Can't move stackable units AND provide amount
            if ( $amount > 0 ) {
                throw new TokenUnitAmountException();
            }

            $amount = count( $units );
        }

        // Get a from wallet
        /** @var Wallet|null $fromWallet */
        $fromWallet = $sourceWallet ?? $this->querySourceWallet( $tokenSlug, $amount );

        // Create a recipient wallet
        $recipientWallet = Wallet::create( $bundleHash, $tokenSlug );

        // Compute the batch ID for the recipient (typically used by stackable tokens)
        if ( $batchId !== null ) {
            $recipientWallet->batchId = $batchId;
        }
        else {
            $recipientWallet->initBatchId( $fromWallet );
        }

        // Remainder wallet
        $this->remainderWallet = Wallet::create( $this->getSecret(), $tokenSlug, $fromWallet->batchId, $fromWallet->characters );
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
     * @param string $tokenSlug
     * @param int $amount
     * @param array $tradeRates
     * @param Wallet|null $sourceWallet
     *
     * @return Response
     * @throws KnishIOException
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     */
    public function depositBufferToken ( string $tokenSlug, int $amount, array $tradeRates, ?Wallet $sourceWallet = null ): Response {

        // Get a from wallet
        /** @var Wallet|null $fromWallet */
        $fromWallet = $sourceWallet ?? $this->querySourceWallet( $tokenSlug, $amount );

        // Remainder wallet
        $this->remainderWallet = Wallet::create( $this->getSecret(), $tokenSlug, $fromWallet->batchId, $fromWallet->characters );
        $this->remainderWallet->initBatchId( $fromWallet, true );

        // Create a molecule with custom source wallet
        $molecule = $this->createMolecule( null, $fromWallet, $this->remainderWallet );

        // Create a mutation
        /** @var MutationDepositBufferToken $query */
        $query = $this->createMoleculeMutation( MutationDepositBufferToken::class, $molecule );

        // Init a molecule & execute it
        $query->fillMolecule( $amount, $tradeRates );
        return $query->execute();
    }

    /**
     * @param string $tokenSlug
     * @param int $amount
     * @param Wallet|null $sourceWallet
     * @param Wallet|null $signingWallet
     *
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function withdrawBufferToken ( string $tokenSlug, int $amount, ?Wallet $sourceWallet = null, ?Wallet $signingWallet = null ): Response {

        // Get a from wallet
        /** @var Wallet|null $fromWallet */
        $fromWallet = $sourceWallet ?? $this->querySourceWallet( $tokenSlug, $amount, 'buffer' );

        // Remainder wallet
        $this->remainderWallet = $fromWallet;

        // Create a molecule with custom source wallet
        $molecule = $this->createMolecule( null, $fromWallet, $this->remainderWallet );

        // Create a mutation
        /** @var MutationWithdrawBufferToken $query */
        $query = $this->createMoleculeMutation( MutationWithdrawBufferToken::class, $molecule );

        // Init a molecule & execute it
        $query->fillMolecule( [ $this->getBundleHash() => $amount, ], $signingWallet );
        return $query->execute();
    }

    /**
     * @param string $tokenSlug
     * @param int $amount
     * @param array $units
     * @param Wallet|null $sourceWallet
     *
     * @return Response
     * @throws KnishIOException
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     */
    public function burnToken ( string $tokenSlug, int $amount, array $units = [], ?Wallet $sourceWallet = null ): Response {

        // Get a from wallet
        /** @var Wallet|null $fromWallet */
        $fromWallet = $sourceWallet ?? $this->querySourceWallet( $tokenSlug, $amount );

        // Remainder wallet
        $remainderWallet = Wallet::create( $this->getSecret(), $tokenSlug, $fromWallet->batchId, $fromWallet->characters );
        $remainderWallet->initBatchId( $fromWallet, true );

        // Calculate amount & set meta key
        if ( count( $units ) > 0 ) {

            // Can't burn stackable units AND provide amount
            if ( $amount > 0 ) {
                throw new TokenUnitAmountException();
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

        // Create & execute a mutation
        $query = $this->createMoleculeMutation( MutationProposeMolecule::class, $molecule );
        return $query->execute();
    }

    /**
     * @param string $tokenSlug
     * @param int $amount
     * @param array $tokenUnits
     * @param Wallet|null $sourceWallet
     *
     * @return Response
     * @throws KnishIOException
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     */
    public function replenishToken ( string $tokenSlug, int $amount, array $tokenUnits = [], ?Wallet $sourceWallet = null ): Response {

        // Get a from wallet
        /** @var Wallet|null $fromWallet */
        $fromWallet = $sourceWallet ?? $this->queryBalance( $tokenSlug )
            ->getPayload();
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

        // Create & execute a mutation
        $query = $this->createMoleculeMutation( MutationProposeMolecule::class, $molecule );
        return $query->execute();
    }

    /**
     * @param string $bundleHash
     * @param string $tokenSlug
     * @param TokenUnit $newTokenUnit
     * @param array $fusedTokenUnitIds
     * @param Wallet|null $sourceWallet
     *
     * @return Response
     * @throws KnishIOException
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     */
    public function fuseToken ( string $bundleHash, string $tokenSlug, TokenUnit $newTokenUnit, array $fusedTokenUnitIds, ?Wallet $sourceWallet = null ): Response {

        // Check bundle hash is secret has passed
        if ( !Crypto::isBundleHash( $bundleHash ) ) {
            throw new WalletShadowException( 'Wrong bundle hash has been passed.' );
        }

        // Get a from wallet
        /** @var Wallet|null $fromWallet */
        $fromWallet = $sourceWallet ?? $this->queryBalance( $tokenSlug )
            ->getPayload();
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
        foreach ( $fromWallet->tokenUnits as $tokenUnit ) {
            $sourceTokenUnitIds[] = $tokenUnit->id;
        }
        foreach ( $fusedTokenUnitIds as $fusedTokenUnitId ) {
            if ( !in_array( $fusedTokenUnitId, $sourceTokenUnitIds, true ) ) {
                throw new TransferWalletException( 'Fused token unit ID = "' . $fusedTokenUnitId . '" does not found in the source wallet.' );
            }
        }

        // Generate new recipient wallet & set the batch ID
        $recipientWallet = Wallet::create( $bundleHash, $tokenSlug );
        $recipientWallet->initBatchId( $fromWallet );

        // Remainder wallet
        $remainderWallet = Wallet::create( $this->getSecret(), $tokenSlug, $fromWallet->batchId, $fromWallet->characters );
        $remainderWallet->initBatchId( $fromWallet, true );

        // Split token units (fused)
        $fromWallet->splitUnits( $fusedTokenUnitIds, $remainderWallet );

        // Set recipient new fused token unit
        $newTokenUnit->metas[ 'fusedTokenUnits' ] = $fromWallet->getTokenUnitsData();
        $recipientWallet->tokenUnits = [ $newTokenUnit ];

        // Create a molecule
        $molecule = $this->createMolecule( null, $fromWallet, $remainderWallet );
        $molecule->fuseToken( $fromWallet->tokenUnits, $recipientWallet );
        $molecule->sign();
        $molecule->check();

        // Create & execute a mutation
        $query = $this->createMoleculeMutation( MutationProposeMolecule::class, $molecule );
        return $query->execute();
    }

    /**
     * @return Wallet
     * @throws GuzzleException
     * @throws JsonException
     * @throws SodiumException
     * @throws KnishIOException
     */
    public function getSourceWallet (): Wallet {
        // Has a ContinuID wallet?
        $sourceWallet = $this->queryContinuId( $this->getBundleHash() )
            ->getPayload();
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
     * @throws KnishIOException
     */
    public function queryContinuId ( string $bundleHash ): Response {
        /**
         * Create & execute the query
         *
         * @var QueryContinuId $query
         */
        $query = $this->createQuery( QueryContinuId::class );

        return $query->execute( [ 'bundleHash' => $bundleHash ] );
    }

    /**
     * @param string $secret
     * @param string|null $cellSlug
     * @param bool $encrypt
     *
     * @return Response
     * @throws GuzzleException
     * @throws JsonException
     * @throws KnishIOException
     * @throws SodiumException
     */
    public function requestAuthToken ( string $secret, string $cellSlug = null, bool $encrypt = false ): Response {
        // Set a cell slug
        if( $cellSlug ) {
            $this->setCellSlug( $cellSlug );
        }

        $this->setSecret( $secret );

        // Querying ContinuID USER wallet
        $wallet = $this->queryBalance( 'USER' )
            ->getPayload();

        // If no ContinuID established, create a new USER wallet
        if ( !$wallet ) {
            $wallet = new Wallet( $secret, 'USER' );
        }

        // Create an auth molecule
        $molecule = $this->createMolecule( $secret, $wallet );

        /**
         * Create query & fill a molecule
         *
         * @var MutationRequestAuthorization $query
         */
        $query = $this->createMoleculeMutation( MutationRequestAuthorization::class, $molecule );
        $query->fillMolecule( [ 'encrypt' => $encrypt ? 'true' : 'false' ] );
        $query->molecule()->sign();

        /**
         * Get a response
         *
         * @var ResponseRequestAuthorization $response
         */
        $response = $query->execute();

        // Create & set an auth token object if the response is successful
        if ( $response->success() ) {
            $payload = $response->getPayload();
            $authToken = AuthToken::create( $payload['U'], $wallet, $encrypt );
            $this->setAuthToken( $authToken );
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

        // Set auth data to apollo client
        $this->client()
            ->setAuthData( $authToken->getToken(), $authToken->getPubkey(), $authToken->getWallet() );

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
