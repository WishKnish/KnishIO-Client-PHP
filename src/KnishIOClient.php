<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use ArrayObject;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlMultiHandler;
use Psr\Http\Message\RequestInterface;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use WishKnish\KnishIO\Client\Exception\CodeException;
use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\UnauthenticatedException;
use WishKnish\KnishIO\Client\Exception\WalletShadowException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Decimal;
use WishKnish\KnishIO\Client\Query\QueryAuthentication;
use WishKnish\KnishIO\Client\Query\QueryBalance;
use WishKnish\KnishIO\Client\Query\QueryContinuId;
use WishKnish\KnishIO\Client\Query\QueryIdentifierCreate;
use WishKnish\KnishIO\Client\Query\QueryMoleculePropose;
use WishKnish\KnishIO\Client\Query\QueryTokenCreate;
use WishKnish\KnishIO\Client\Query\QueryTokenReceive;
use WishKnish\KnishIO\Client\Query\QueryTokenTransfer;
use WishKnish\KnishIO\Client\Query\QueryShadowWalletClaim;
use WishKnish\KnishIO\Client\Query\QueryWalletList;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\HttpClient\HttpClient;
use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;


/**
 * Class KnishIO
 * @package WishKnish\KnishIO\Client
 */
class KnishIOClient
{

    /**
     * @var string
     */
	private $url;

    /**
     * @var Client
     */
	private $client;

    /**
     * @var string
     */
    private $secret;

	/**
	 * @var string
	 */
    private $cellSlug;


	/**
	 * KnishIOClient constructor.
	 * @param $url
	 * @param HttpClientInterface|null $client
	 */
	public function __construct ( $url, HttpClientInterface $client = null )
	{
        $this->url = $url;
        $this->client = default_if_null( $client, new HttpClient($url) );
	}


	/**
	 * @return string
	 */
	public function url ()
	{
		return $this->url;
	}


	/**
	 * @todo unify code with url & setUrl functions
	 * @param $url
	 */
	public function setUrl ($url) {
		$this->url = $url;
		$this->client->setUrl($url);
	}


	/**
	 * @return string
	 */
	public function cellSlug ()
	{
		return $this->cellSlug;
	}


	/**
	 * @param $cellSlug
	 */
	public function setCellSlug ($cellSlug)
	{
		$this->cellSlug = $cellSlug;
	}


	/**
	 * @return Client|mixed
	 */
	public function client ()
	{
		return $this->client;
	}


	/**
	 * @param null $sourceWallet
	 * @param null $remainderWallet
	 * @return Molecule
	 * @throws Exception
	 */
	public function createMolecule ( $sourceWallet = null, $remainderWallet = null )
	{
		$sourceWallet = $sourceWallet ?? $this->getSourceWallet();
		$remainderWallet = $remainderWallet ??
			Wallet::create( $this->secret(), $sourceWallet->token, $sourceWallet->batchId, $sourceWallet->characters );

		return new Molecule( $this->secret(), $sourceWallet, $remainderWallet, $this->cellSlug );
	}


	/**
	 * @param $class
	 * @return mixed
	 */
	public function createQuery ( $class )
    {
		return new $class( $this->client, $this->url );
	}


	/**
	 * @param $class
	 * @param Molecule|null $molecule
	 * @return mixed
	 */
	public function createMoleculeQuery ( $class, Molecule $molecule = null )
	{
		// Init molecule
		$molecule = $molecule ?? $this->createMolecule();

		// Create base query
		$query = new $class ( $this->client, $molecule, $this->url );

		// Only instances of QueryMoleculePropose supported
		if ( !$query instanceof QueryMoleculePropose ) {
			throw new CodeException(static::class.'::createMoleculeQuery - required class instance of QueryMoleculePropose.');
		}

		return $query;
	}


	/**
	 * @param string $code
	 * @param string $token
	 * @return Response
	 * @throws Exception
	 */
	public function getBalance ( $code, $token )
	{
		// Create a query
        /** @var QueryBalance $query */
		$query = $this->createQuery(QueryBalance::class );

		// If bundle hash came, we pass it, otherwise we consider it a secret
		$bundleHash = Wallet::isBundleHash( $code ) ? $code : Crypto::generateBundleHash( $code );

       // Execute the query
		return $query->execute( [
            'bundleHash' => $bundleHash,
            'token'      => $token,
        ] );
	}


    /**
     * @param string $secret
     * @param string $token
     * @param int|float $amount
     * @param array $metas
     * @return Response
     * @throws Exception
     */
	public function createToken ( $token, $amount, array $metas = null )
	{
		$metas = default_if_null( $metas, [] );

		// Recipient wallet
		$recipientWallet = new Wallet( $this->secret(), $token );

		if ( array_get( $metas, 'fungibility' ) === 'stackable' ) { // For stackable token - create a batch ID
			$recipientWallet->batchId = Wallet::generateBatchId();
		}

		// Create a query
        /** @var QueryTokenCreate $query */
		$query = $this->createMoleculeQuery(QueryTokenCreate::class );

		// Init a molecule
		$query->fillMolecule ( $recipientWallet, $amount, $metas );

        // Return a query execution result
		return $query->execute ();
	}


	/**
	 * @param string $secret
	 * @param string $token
	 * @param int|float $value
	 * @param Wallet|string $to wallet address OR bundle
	 * @param array|null $metas
	 * @return mixed
	 * @throws Exception
	 */
	public function receiveToken ( $token, $value, $to, array $metas = null )
	{
		$metas = default_if_null( $metas, [] );

		// Is a string? $to is bundle or secret
		if ( is_string( $to ) ) {

			// Bundle: set metaType
			if (Wallet::isBundleHash( $to ) ) {
				$metaType = 'walletbundle';
				$metaId = $to;
			}

			// Secret: create a new wallet (not shadow)
			else {
				$to = Wallet::create( $to, $token );
			}
		}

		// Is a wallet object?
		if ( $to instanceof Wallet ) {

			// Meta type: wallet
			$metaType = 'wallet';

			// Set wallet metas
			$metas = array_merge($metas, [
				'position' => $to->position,
				'bundle' => $to->bundle,
			]);

			// Set metaId as an wallet address
			$metaId = $to->address;
		}

		// Create a query
        /** @var QueryTokenReceive $query */
		$query = $this->createMoleculeQuery(QueryTokenReceive::class);

		// Init a molecule
		$query->fillMolecule ( $token, $value, $metaType, $metaId, $metas );

		// Return a query execution result
		return $query->execute ();
	}


    /**
     * @param string $secret
     * @param $type
     * @param $contact
     * @param $code
     * @return mixed
     * @throws Exception
     */
	public function createIdentifier ( $type, $contact, $code )
	{

		// Create & execute a query
        /** @var QueryIdentifierCreate $query */
		$query = $this->createMoleculeQuery(QueryIdentifierCreate::class);

		// Init a molecule
		$query->fillMolecule ( $type, $contact, $code );

		// Execute a query
		return $query->execute();
	}


	/**
	 * @param $token
	 */
	public function getShadowWallets ( $token ) {

		// --- Get shadow wallet list
		$query = $this->createQuery(QueryWalletList::class);
		$response = $query->execute([
			'bundleHash'	=> Crypto::generateBundleHash( $this->secret() ),
			'token'			=> $token,
		]);
		$shadowWallets = $response->payload();

		// Check shadow wallets
		if (!$shadowWallets) {
			throw new WalletShadowException();
		}
		foreach ($shadowWallets as $shadowWallet) {
			if (!$shadowWallet instanceof WalletShadow) {
				throw new WalletShadowException();
			}
		}

		return $shadowWallets;
	}


	/**
	 * Claim a shadow wallet
	 *
	 * @param $token
	 * @param null $molecule
	 * @return mixed
	 * @throws Exception
	 */
	public function claimShadowWallet ( $token, $molecule = null )
	{
		// Get shadow wallet list
		$shadowWallets = $this->getShadowWallets( $token );

		// Create a query
		$query = $this->createMoleculeQuery( QueryShadowWalletClaim::class, $molecule );

		// Fill a molecule
		$query->fillMolecule( $token, $shadowWallets );

		// Return a response
		return $query->execute();
	}


	/**
	 * @param string $fromSecret
	 * @param string|Wallet $to
	 * @param string $token
	 * @param int|float $amount
	 * @return array
	 * @throws Exception|ReflectionException|InvalidResponseException
	 */
	public function transferToken ( $to, $token, $amount )
	{

		// Get a from wallet
        /** @var Wallet|null $fromWallet */
		$fromWallet = $this->getBalance( $this->secret(), $token )->payload();

		if ( $fromWallet === null || Decimal::cmp( $fromWallet->balance, $amount ) < 0) {
			throw new TransferBalanceException( 'The transfer amount cannot be greater than the sender\'s balance' );
		}

		// If this wallet is assigned, if not, try to get a valid wallet
        /** @var Wallet $toWallet */
		$toWallet = $to instanceof Wallet ? $to : $this->getBalance( $to, $token )->payload();

		// Has not wallet yet - create it
		if ($toWallet === null) {
			$toWallet = Wallet::create( $to, $token );
		}

		// Batch ID initialization
		$toWallet->initBatchId( $fromWallet, $amount );

		// Remainder wallet
		$remainderWallet = Wallet::create( $this->secret(), $token, $toWallet->batchId, $fromWallet->characters );

		// Create a molecule with custom source wallet
		$molecule = $this->createMolecule ( $fromWallet, $remainderWallet );

		// Create a query
        /** @var QueryTokenTransfer $query */
		$query = $this->createMoleculeQuery(QueryTokenTransfer::class, $molecule);

		// Init a molecule
		$query->fillMolecule ( $toWallet, $amount );

		// Execute a query
		return $query->execute();
	}


	/**
	 * @param $secret
	 * @return Wallet|null
	 * @throws Exception
	 */
	public function getSourceWallet () {

		// Has a ContinuID wallet?
		$sourceWallet = $this->getContinuId( Crypto::generateBundleHash( $this->secret() ) )->payload();
		if ($sourceWallet) {
			return $sourceWallet;
		}

		// Create a new source wallet
		return new Wallet( $this->secret() );
	}


	/**
	 * @param $bundleHash
	 * @return mixed
	 */
	public function getContinuId ( $bundleHash )
    {
        // Create & execute the query
        return $this->createQuery(QueryContinuId::class )
			->execute( ['bundle' => $bundleHash] );
    }


    /**
	 * @param string $secret
	 * @throws Exception
	 */
	public function authentication ( $secret, $cell_slug = null )
	{
		// Set a secret
		$this->secret = $secret;

		// Set a cell slug
		$this->cellSlug = $cell_slug;

		// Get a ContinuId wallet
		$wallet = $this->getSourceWallet( $secret );

		// Create query & fill a molecule
		$query = $this->createMoleculeQuery( QueryAuthentication::class );
		$query->fillMolecule( $secret, $wallet );

		// Get a response
		$response = $query->execute();

		// If the response is success - set auth token
		if ($response->success() ) {
			$this->client->setAuthToken( $response->token() );
		}

		// Not authorized: throw an exception
		else {
			dd ($response);
			throw new UnauthenticatedException($response->reason());
		}

		return $response;
	}


	/**
	 * @return string
	 */
	public function secret ()
	{
		if ( !$this->secret )
		{
			throw new UnauthenticatedException( 'Expected '.static::class.'::authentication call before.' );
		}

		return $this->secret;
	}


}
