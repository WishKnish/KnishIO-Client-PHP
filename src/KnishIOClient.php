<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use Exception;
use ReflectionException;
use WishKnish\KnishIO\Client\Exception\CodeException;
use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\UnauthenticatedException;
use WishKnish\KnishIO\Client\Exception\WalletShadowException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Decimal;
use WishKnish\KnishIO\Client\Mutation\MutationCreateMeta;
use WishKnish\KnishIO\Client\Mutation\MutationCreateWallet;
use WishKnish\KnishIO\Client\Query\QueryBalance;
use WishKnish\KnishIO\Client\Query\QueryContinuId;
use WishKnish\KnishIO\Client\Mutation\MutationRequestAuthorization;
use WishKnish\KnishIO\Client\Mutation\MutationCreateIdentifier;
use WishKnish\KnishIO\Client\Mutation\MutationProposeMolecule;
use WishKnish\KnishIO\Client\Mutation\MutationCreateToken;
use WishKnish\KnishIO\Client\Mutation\MutationRequestTokens;
use WishKnish\KnishIO\Client\Mutation\MutationTransferTokens;
use WishKnish\KnishIO\Client\Mutation\MutationClaimShadowWallet;
use WishKnish\KnishIO\Client\Query\QueryMetaType;
use WishKnish\KnishIO\Client\Query\QueryWalletBundle;
use WishKnish\KnishIO\Client\Query\QueryWalletList;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\HttpClient\HttpClient;
use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;

/**
 * Class KnishIO
 * @package WishKnish\KnishIO\Client
 */
class KnishIOClient {

  /**
   * @var HttpClient
   */
  private $client;

  /**
   * @var string
   */
  private $secret;

  /**
   * @var string
   */
  private $bundle;

  /**
   * @var
   */
  private $remainderWallet;

  /**
   * @var
   */
  private $lastMoleculeQuery;

  /**
   * @var string
   */
  private $cellSlug;

  /**
   * KnishIOClient constructor.
   *
   * @param null $url
   * @param HttpClientInterface|null $client
   */
  public function __construct (
    $url = null,
    HttpClientInterface $client = null,
    $serverSdkVersion = 3
  ) {
    $url = $url ?: url() . '/graphql';
    $this->initialize(
      $url,
      $client,
      $serverSdkVersion
    );
  }

  /**
   * @param $url
   * @param null $client
   * @param int $serverSdkVersion
   */
  public function initialize (
    $url,
    $client = null,
    $serverSdkVersion = 3
  ) {
    $this->reset();

    $this->client = default_if_null(
      $client,
      new HttpClient( $url )
    );
    $this->serverSdkVersion = $serverSdkVersion;
  }

  /**
   * Reset common properties
   */
  public function reset () {
    $this->secret = null;
    $this->bundle = null;
    $this->remainderWallet = null;
  }

  /**
   * @return string|null
   */
  public function cellSlug () {
    return $this->cellSlug;
  }

  /**
   * @param $cellSlug
   */
  public function setCellSlug ( $cellSlug ) {
    $this->cellSlug = $cellSlug;
  }

  /**
   * @return string
   */
  public function url () {
    return $this->client->getUrl();
  }

  /**
   * @return HttpClient
   * @todo rename to HttpClient!
   */
  public function client () {
    return $this->client;
  }

  /**
   * Has a secret?
   */
  public function hasSecret (): bool {
    return $this->secret ? true : false;
  }

  /**
   * @param $secret
   */
  public function setSecret ( $secret ) {
    $this->secret = $secret;
    $this->bundle = Crypto::generateBundleHash( $secret );
  }

  /**
   * @return string
   */
  public function secret () {
    if ( !$this->secret ) {
      throw new UnauthenticatedException( 'Expected ' . static::class . '::authentication call before.' );
    }

    return $this->secret;
  }

  /**
   * Returns the bundle hash for this session
   *
   * @returns {string}
   */
  public function bundle () {
    if ( !$this->bundle ) {
      throw new UnauthenticatedException( 'KnishIOClient::bundle() - Unable to find a stored bundle!' );
    }
    return $this->bundle;
  }

  /**
   * @return mixed
   */
  public function getRemainderWallet () {
    return $this->remainderWallet;
  }

  /**
   * @param null $secret
   * @param null $sourceWallet
   * @param null $remainderWallet
   *
   * @return Molecule
   * @throws Exception
   */
  public function createMolecule (
    $secret = null,
    $sourceWallet = null,
    $remainderWallet = null
  ) {
    // Secret
    $secret = $secret ?: $this->secret();

    // Is source wallet passed & has a last success query? Update a source wallet with a remainder one
    if ( $sourceWallet === null && $this->remainderWallet->token !== 'AUTH' && $this->lastMoleculeQuery && $this->lastMoleculeQuery->response(
      ) && $this->lastMoleculeQuery->response()
        ->success() ) {
      $sourceWallet = $this->remainderWallet;
    }

    // Get source wallet by ContinuID query
    if ( $sourceWallet === null ) {
      $sourceWallet = $this->getSourceWallet();
    }

    // Remainder wallet
    $this->remainderWallet = $remainderWallet ?: Wallet::create(
      $secret,
      'USER',
      $sourceWallet->batchId,
      $sourceWallet->characters
    );

    return new Molecule(
      $secret,
      $sourceWallet,
      $this->remainderWallet,
      $this->cellSlug
    );
  }

  /**
   * @param $class
   *
   * @return mixed
   */
  public function createQuery ( $class ) {
    return new $class( $this->client );
  }

  /**
   * @param $class
   * @param Molecule|null $molecule
   *
   * @return mixed
   * @throws Exception
   */
  public function createMoleculeMutation (
    $class,
    Molecule $molecule = null
  ) {

    // Init molecule
    $molecule = $molecule ?: $this->createMolecule();

    // Create base query
    $query = new $class (
      $this->client,
      $molecule
    );

    // Only instances of MutationProposeMolecule supported
    if ( !$query instanceof MutationProposeMolecule ) {
      throw new CodeException(
        static::class . '::createMoleculeMutation - required class instance of MutationProposeMolecule.'
      );
    }

    // Save the last molecule query
    $this->lastMoleculeQuery = $query;

    return $query;
  }

  /**
   * @param null $secret
   * @param null $cell_slug
   *
   * @return mixed
   * @throws Exception
   */
  public function requestAuthToken (
    $secret,
    $cell_slug = null
  ) {
    // Set a secret
    $this->setSecret( $secret );

    // Set a cell slug
    $this->cellSlug = $cell_slug ?: $this->cellSlug();

    // Create an auth molecule
    $molecule = $this->createMolecule(
      $this->secret,
      new Wallet(
        $this->secret,
        'AUTH'
      )
    );

    // Create query & fill a molecule
    $query = $this->createMoleculeMutation(
      MutationRequestAuthorization::class,
      $molecule
    );
    $query->fillMolecule();

    // Get a response
    $response = $query->execute();

    // If the response is success - set auth token
    if ( $response->success() ) {
      $this->client->setAuthToken( $response->token() );
    } // Not authorized: throw an exception
    else {
      throw new UnauthenticatedException( $response->reason() );
    }

    return $response;
  }

  /**
   * @param $code
   * @param $token
   *
   * @return Response
   * @throws Exception
   */
  public function queryBalance (
    $tokenSlug,
    $bundleHash = null
  ): Response {

    // Create a query
    /** @var QueryBalance $query */
    $query = $this->createQuery( QueryBalance::class );

    // Execute the query
    return $query->execute(
      [
        'bundleHash' => $bundleHash ?: $this->bundle(),
        'token' => $tokenSlug,
      ]
    );
  }

  /**
   * @param $code
   * @param $token
   *
   * @return Response
   * @throws Exception
   */
  public function queryMeta (
    $metaType,
    $metaId = null,
    $key = null,
    $value = null,
    $latest = null,
    $fields = null
  ): Response {

    // Create a query
    /** @var QueryMetaType $query */
    $query = $this->createQuery( QueryMetaType::class );
    $variables = QueryMetaType::createVariables(
      $metaType,
      $metaId,
      $key,
      $value,
      $latest
    );

    // Execute the query
    return $query->execute(
      $variables,
      $fields
    )
      ->payload();

  }

  /**
   * @param string $tokenSlug
   */
  public function createWallet ( string $tokenSlug ) {
    $newWallet = new Wallet(
      $this->secret(),
      $tokenSlug
    );

    $query = $this->createQuery( MutationCreateWallet::class );
    $query->fillMolecule( $newWallet );

    // Execute the query
    return $query->execute();
  }

  /**
   * @param $tokenSlug
   * @param $initialAmount
   * @param array|null $tokenMetadata
   *
   * @return mixed
   * @throws ReflectionException
   */
  public function createToken (
    $tokenSlug,
    $initialAmount,
    array $tokenMetadata = null,
    string $batchId = null
  ) {
    $tokenMetadata = default_if_null(
      $tokenMetadata,
      []
    );

    // Recipient wallet
    $recipientWallet = new Wallet(
      $this->secret(),
      $tokenSlug
    );

    if ( array_get(
        $tokenMetadata,
        'fungibility'
      ) === 'stackable' ) { // For stackable token - create a batch ID
      $recipientWallet->batchId = $batchId ?? Wallet::generateBatchId();
    }

    // Create a query
    /** @var MutationCreateToken $query */
    $query = $this->createMoleculeMutation( MutationCreateToken::class );

    // Init a molecule
    $query->fillMolecule(
      $recipientWallet,
      $initialAmount,
      $tokenMetadata
    );

    // Return a query execution result
    return $query->execute();
  }

  /**
   * @param string $metaType
   * @param string $metaId
   * @param array|null $metadata
   */
  public function createMeta (
    string $metaType,
    string $metaId,
    array $metadata = null
  ) {

    // Create a custom molecule
    $molecule = $this->createMolecule(
      $this->secret(),
      $this->getSourceWallet()
    );

    // Create & execute a query
    /** @var MutationCreateMeta $query */
    $query = $this->createMoleculeMutation(
      MutationCreateMeta::class,
      $molecule
    );

    // Init a molecule
    $query->fillMolecule(
      $metaType,
      $metaId,
      $metadata
    );

    // Execute a query
    return $query->execute();
  }

  /**
   * @param $type
   * @param $contact
   * @param $code
   *
   * @return mixed
   * @throws Exception
   */
  public function createIdentifier (
    $type,
    $contact,
    $code
  ) {

    // Create & execute a query
    /** @var MutationCreateIdentifier $query */
    $query = $this->createMoleculeMutation( MutationCreateIdentifier::class );

    // Init a molecule
    $query->fillMolecule(
      $type,
      $contact,
      $code
    );

    // Execute a query
    return $query->execute();
  }

  /**
   * @param null $bundleHash
   * @param bool $unspent
   */
  public function queryWallets (
    string $bundleHash = null,
    bool $unspent = true
  ) {
    $query = $this->createQuery( QueryWalletList::class );
    $response = $query->execute(
      [
        'bundleHash' => $bundleHash ? $bundleHash : $this->bundle(),
        'unspent' => $unspent,
      ]
    );

    return $response->getWallets();
  }

  /**
   * @param string $tokenSlug
   * @param string|null $bundleHash
   *
   * @return mixed
   */
  public function queryShadowWallets (
    string $tokenSlug = 'KNISH',
    string $bundleHash = null
  ) {

    // --- Get shadow wallet list
    $query = $this->createQuery( QueryWalletList::class );
    $response = $query->execute(
      [
        'bundleHash' => $bundleHash ?? $this->bundle(),
        'token' => $tokenSlug,
      ]
    );

    return $response->payload();
  }

  /**
   * @param string|null $bundleHash
   * @param string|null $key
   * @param string|null $value
   * @param bool $latest
   * @param array|null $fields
   *
   * @return mixed
   */
  public function queryBundle (
    string $bundleHash = null,
    string $key = null,
    string $value = null,
    bool $latest = true,
    array $fields = null
  ) {

    // Create a query
    /** @var QueryWalletBundle $query */
    $query = $this->createQuery( QueryWalletBundle::class );
    $variables = QueryWalletBundle::createVariables(
      $bundleHash,
      $key,
      $value,
      $latest
    );

    // Execute the query
    return $query->execute(
      $variables,
      $fields
    );

    return $response->payload();
  }

  /**
   * @param string $tokenSlug
   * @param int|float $requestedAmount
   * @param Wallet|string $to wallet address OR bundle
   * @param array|null $metas
   *
   * @return mixed
   * @throws Exception
   */
  public function requestTokens (
    $tokenSlug,
    $requestedAmount,
    $to,
    array $metas = null
  ) {
    $metas = default_if_null(
      $metas,
      []
    );

    // Is a string? $to is bundle or secret
    if ( is_string( $to ) ) {

      // Bundle: set metaType
      if ( Wallet::isBundleHash( $to ) ) {
        $metaType = 'walletbundle';
        $metaId = $to;
      } // Secret: create a new wallet (not shadow)
      else {
        $to = Wallet::create(
          $to,
          $tokenSlug
        );
      }
    }

    // Is a wallet object?
    if ( $to instanceof Wallet ) {

      // Meta type: wallet
      $metaType = 'wallet';

      // Set wallet metas
      $metas = array_merge(
        $metas,
        [
          'position' => $to->position,
          'bundle' => $to->bundle,
        ]
      );

      // Set metaId as an wallet address
      $metaId = $to->address;
    }

    // Create a query
    /** @var MutationRequestTokens $query */
    $query = $this->createMoleculeMutation( MutationRequestTokens::class );

    // Init a molecule
    $query->fillMolecule(
      $tokenSlug,
      $requestedAmount,
      $metaType,
      $metaId,
      $metas
    );

    // Return a query execution result
    return $query->execute();
  }

  /**
   * Claim a shadow wallet
   *
   * @param string $tokenSlug
   * @param string $batchId | for fungible tokens batchId = null
   * @param null $molecule
   *
   * @return mixed|Response
   * @throws Exception
   */
  public function claimShadowWallet (
    string $tokenSlug,
    ?string $batchId,
    $molecule = null
  ) {
    // Create a query
    $query = $this->createMoleculeMutation(
      MutationClaimShadowWallet::class,
      $molecule
    );
    $query->fillMolecule(
      $tokenSlug,
      $batchId
    );

    // Return a response
    return $query->execute();
  }

  /**
   * @param string $tokenSlug
   *
   * @return array
   * @throws Exception
   */
  public function claimShadowWallets ( string $tokenSlug ): array {
    // Get shadow wallet list
    $shadowWallets = $this->queryShadowWallets( $tokenSlug );

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
      $responses[] = $this->claimShadowWallet(
        $tokenSlug,
        $shadowWallet->batchId
      );
    }
    return $responses;
  }

  /**
   * @param string|Wallet $walletObjectOrBundleHash
   * @param string $tokenSlug
   * @param int|float $amount
   *
   * @return array
   * @throws Exception|ReflectionException|InvalidResponseException
   */
  public function transferToken (
    $walletObjectOrBundleHash,
    $tokenSlug,
    $amount,
    string $batchId = null
  ) {

    // Get a from wallet
    /** @var Wallet|null $fromWallet */
    $fromWallet = $this->queryBalance(
      $tokenSlug,
      $this->bundle()
    )
      ->payload();

    if ( $fromWallet === null || Decimal::cmp(
        $fromWallet->balance,
        $amount
      ) < 0 ) {
      throw new TransferBalanceException( 'The transfer amount cannot be greater than the sender\'s balance' );
    }

    // Get final bundle hash
    $bundleHash = Wallet::isBundleHash(
      $walletObjectOrBundleHash
    ) ? $walletObjectOrBundleHash : Crypto::generateBundleHash( $walletObjectOrBundleHash );

    // If this wallet is assigned, if not, try to get a valid wallet
    /** @var Wallet $toWallet */
    $toWallet = $walletObjectOrBundleHash instanceof Wallet ? $walletObjectOrBundleHash : $this->queryBalance(
      $tokenSlug,
      $bundleHash
    )
      ->payload();

    // Has not wallet yet - create it
    if ( $toWallet === null ) {
      $toWallet = Wallet::create(
        $walletObjectOrBundleHash,
        $tokenSlug
      );
    }

    // Batch ID initialization
    $toWallet->initBatchId(
      $fromWallet,
      $amount
    );
    if ( $batchId !== null ) {
      $toWallet->batchId = $batchId;
    }

    // Remainder wallet
    $this->remainderWallet = Wallet::create(
      $this->secret(),
      $tokenSlug,
      $toWallet->batchId,
      $fromWallet->characters
    );

    // Create a molecule with custom source wallet
    $molecule = $this->createMolecule(
      null,
      $fromWallet,
      $this->remainderWallet
    );

    // Create a query
    /** @var MutationTransferTokens $query */
    $query = $this->createMoleculeMutation(
      MutationTransferTokens::class,
      $molecule
    );

    // Init a molecule
    $query->fillMolecule(
      $toWallet,
      $amount
    );

    // Execute a query
    return $query->execute();
  }

  /**
   * @param bool $onlyValue
   *
   * @return Wallet
   * @throws Exception
   */
  public function getSourceWallet () {
    // Has a ContinuID wallet?
    $sourceWallet = $this->queryContinuId( Crypto::generateBundleHash( $this->secret() ) )
      ->payload();
    if ( !$sourceWallet ) {
      $sourceWallet = new Wallet( $this->secret() );
    }

    // Return final source wallet
    return $sourceWallet;
  }

  /**
   * @param $bundleHash
   *
   * @return mixed
   */
  public function queryContinuId ( $bundleHash ) {
    // Create & execute the query
    return $this->createQuery( QueryContinuId::class )
      ->execute( [ 'bundle' => $bundleHash ] );
  }

}
