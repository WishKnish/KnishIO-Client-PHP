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
use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\WalletShadowException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Decimal;
use WishKnish\KnishIO\Client\Query\QueryAuthentication;
use WishKnish\KnishIO\Client\Query\QueryBalance;
use WishKnish\KnishIO\Client\Query\QueryContinueId;
use WishKnish\KnishIO\Client\Query\QueryIdentifierCreate;
use WishKnish\KnishIO\Client\Query\QueryTokenCreate;
use WishKnish\KnishIO\Client\Query\QueryTokenReceive;
use WishKnish\KnishIO\Client\Query\QueryTokenTransfer;
use WishKnish\KnishIO\Client\Query\QueryWalletClaim;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Middleware\RetryGuzzleMiddleware;
use WishKnish\KnishIO\HttpClient\HttpClient;
use WishKnish\KnishIO\HttpClient\HttpClientInterface;


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
	 * @param $class
	 * @return mixed
	 */
	public function createQuery ( $class )
    {
		return new $class ( $this->client, $this->url );
	}

    /**
     * @return string
     */
	public function getSecret ()
    {
        return $this->secret;
    }

	/**
	 * @param string $code
	 * @param string $token
	 * @return Response
	 * @throws Exception
	 */
	public function getBalance ( $code, $token )
	{
        $this->secret = $code;

		// Create a query
        /** @var QueryBalance $query */
		$query = $this->createQuery(QueryBalance::class );

		// If bundle hash came, we pass it, otherwise we consider it a secret
		$bundleHash = Wallet::isBundleHash( $code ) ? $code : Crypto::generateBundleHash( $code );

       // Execute the query
		return $query->execute( [
            'bundleHash' => $bundleHash,
            'token'      => $token,
        ], $this->addPending( __FUNCTION__, func_get_args() ) );
	}

    /**
     * @param string $secret
     * @param string $token
     * @param int|float $amount
     * @param array $metas
     * @return Response
     * @throws Exception
     */
	public function createToken ( $secret, $token, $amount, array $metas = null )
	{
        $this->secret = $secret;

		$metas = default_if_null( $metas, [] );

		// Source wallet
		$sourceWallet = default_if_null( $this->getContinueId( $secret )->payload(), new Wallet( $secret ) );

		// Recipient wallet
		$recipientWallet = new Wallet( $secret, $token );

		if ( array_get( $metas, 'fungibility' ) === 'stackable' ) { // For stackable token - create a batch ID
			$recipientWallet->batchId = Wallet::generateBatchId();
		}

		// Create a query
        /** @var QueryTokenCreate $query */
		$query = $this->createQuery(QueryTokenCreate::class );

		// Init a molecule
		$query->initMolecule ( $secret, $sourceWallet, $recipientWallet, $amount, $metas );

        // Return a query execution result
		return $query->execute ( null, $this->addPending( __FUNCTION__, func_get_args() ) );
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
	public function receiveToken ( $secret, $token, $value, $to, array $metas = null )
	{
        $this->secret = $secret;

		$metas = default_if_null( $metas, [] );

		// Source wallet
		$sourceWallet = default_if_null( $this->getContinueId( $secret )->payload(),  new Wallet( $secret ) );

		// Meta type
		$metaType = Wallet::isBundleHash( $to ) ? 'walletbundle' : 'wallet';

		// Create a query
        /** @var QueryTokenReceive $query */
		$query = $this->createQuery(QueryTokenReceive::class);

		// Init a molecule
		$query->initMolecule ( $secret, $sourceWallet, $token, $value, $metaType, $to, $metas );

		// Return a query execution result
		return $query->execute ( null, $this->addPending( __FUNCTION__, func_get_args() ) );
	}

    /**
     * @param string $secret
     * @param $type
     * @param $contact
     * @param $code
     * @return mixed
     * @throws Exception
     */
	public function createIdentifier ( $secret, $type, $contact, $code )
	{
        $this->secret = $secret;

		// Create source & remainder wallets
		$sourceWallet = default_if_null( $this->getContinueId( $secret )->payload(), new Wallet( $secret ) );

		// Create & execute a query
        /** @var QueryIdentifierCreate $query */
		$query = $this->createQuery(QueryIdentifierCreate::class);

		// Init a molecule
		$query->initMolecule ( $secret, $sourceWallet, $type, $contact, $code);

		// Execute a query
		return $query->execute( null, $this->addPending( __FUNCTION__, func_get_args() ) );
	}

    /**
     * Bind shadow wallet
     *
     * @param string $secret
     * @param string $token
     * @param Wallet|null $sourceWallet
     * @param Wallet|null $shadowWallet
     * @param null $recipientWallet
     * @return mixed
     * @throws Exception
     */
	public function claimShadowWallet ( $secret, $token, Wallet $sourceWallet = null, Wallet $shadowWallet = null, $recipientWallet = null )
    {

        $this->secret = $secret;

		// Source wallet
		$sourceWallet = default_if_null( $sourceWallet, default_if_null( $this->getContinueId( $secret )->payload(), new Wallet( $secret ) ) );

		// Shadow wallet (to get a Batch ID & balance from it)
		$shadowWallet = default_if_null( $shadowWallet, $this->getBalance($secret, $token)->payload() );

		if ( $shadowWallet === null || !$shadowWallet instanceof WalletShadow ) {
			throw new WalletShadowException();
		}

		// Create a query
        /** @var QueryWalletClaim $query */
		$query = $this->createQuery( QueryWalletClaim::class );

		// Init a molecule
		$query->initMolecule( $secret, $sourceWallet, $shadowWallet, $token, $recipientWallet );

		// Execute a query
		return $query->execute( null, $this->addPending( __FUNCTION__, func_get_args() ) );
	}

	/**
	 * @param string $fromSecret
	 * @param string|Wallet $to
	 * @param string $token
	 * @param int|float $amount
	 * @return array
	 * @throws Exception|ReflectionException|InvalidResponseException
	 */
	public function transferToken ( $fromSecret, $to, $token, $amount, Wallet $remainderWallet = null )
	{
        $this->secret = $fromSecret;

		// Get a from wallet
        /** @var Wallet|null $fromWallet */
		$fromWallet = $this->getBalance( $fromSecret, $token )->payload();

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

		// Create a query
        /** @var QueryTokenTransfer $query */
		$query = $this->createQuery(QueryTokenTransfer::class);

		// Init a molecule
		$query->initMolecule ( $fromSecret, $fromWallet, $toWallet, $token, $amount, $remainderWallet );

		// Execute a query
		return $query->execute( null, $this->addPending( __FUNCTION__, func_get_args() ) );
	}

    /**
     * @param string $bundleOrSecret
     * @return Response
     * @throws Exception
     */
	public function getContinueId ( $bundleOrSecret )
    {
        $this->secret = $bundleOrSecret;
        /** @var  QueryContinueId $query */
        $query = $this->createQuery(QueryContinueId::class );

        // Execute the query
        return $query->execute( [
            'bundle' => Wallet::isBundleHash( $bundleOrSecret ) ? $bundleOrSecret : Crypto::generateBundleHash( $bundleOrSecret ),
        ] );
    }

    /**
     * @param string $secret
     * @throws Exception
     */
    public function authentication ( $secret )
    {
        $this->secret = $secret;

        /** @var Wallet $wallet */
        $wallet = $this->getContinueId( $secret )->payload() ?: new Wallet( $secret );
        /** @var QueryAuthentication $query */
        $query = $this->createQuery( QueryAuthentication::class );
        $query->initMolecule( $secret, $wallet );

        return $query->execute();
    }
}
