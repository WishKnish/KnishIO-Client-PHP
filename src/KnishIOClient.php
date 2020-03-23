<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use Exception;
use GuzzleHttp\Client;
use ReflectionException;
use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Exception\TransferBalanceException;
use WishKnish\KnishIO\Client\Exception\WalletShadowException;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Decimal;
use WishKnish\KnishIO\Client\Query\QueryBalance;
use WishKnish\KnishIO\Client\Query\QueryIdentifierCreate;
use WishKnish\KnishIO\Client\Query\QueryMoleculePropose;
use WishKnish\KnishIO\Client\Query\QueryTokenCreate;
use WishKnish\KnishIO\Client\Query\QueryTokenReceive;
use WishKnish\KnishIO\Client\Query\QueryTokenTransfer;
use WishKnish\KnishIO\Client\Query\QueryShadowWalletClaim;
use WishKnish\KnishIO\Client\Query\QueryWalletList;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Exception\CodeException;

/**
 * Class KnishIO
 * @package WishKnish\KnishIO\Client
 */
class KnishIOClient
{
	// Client parameters
	private $url;
	private $client;

	// Saved source wallets to keep last source wallet value for ContinuID
	private $sourceWallets = []; // [secret1 => Wallet1, secret2 => Wallet2, etc...]


	/**
	 * KnishIO constructor.
	 * @param string $url
	 * @param Client|null $client
	 */
	public function __construct ($url, Client $client = null)
	{
		$this->client = \default_if_null($client, new Client( [
			'base_uri'    => $url,
			'verify'      => false,
			'http_errors' => false,
			'headers'     => [
				'User-Agent' => 'KnishIO/0.1',
				'Accept'     => 'application/json',
			]
		] ) );
		$this->url = $url;
	}


	/**
	 * @param $secret
	 * @param $wallet
	 */
	public function setSourceWallet ($secret, $wallet) {
		$this->sourceWallets[$secret] = $wallet;
	}


	/**
	 * @param $secret
	 * @return mixed
	 * @throws Exception
	 */
	public function getSourceWallet ($secret) {
		return array_get($this->sourceWallets, $secret, new Wallet( $secret ) );
	}


	/**
	 * @return mixed
	 */
	public function client () {
		return $this->client;
	}


	/**
	 * @param $url
	 */
	public function setUrl ($url) {
		$this->url = $url;
	}


	/**
	 * @return string
	 */
	public function url () {
		return $this->url;
	}


	/**
	 * @param $class
	 * @return mixed
	 */
	public function createQuery ($class) {
		return new $class ($this->client, $this->url);
	}


	/**
	 * @param $class
	 * @param Molecule|null $molecule
	 */
	public function createMoleculeQuery ($class, Molecule $molecule = null) {

		// Create base query
		$query = $this->createQuery($class);

		// Only instances of QueryMoleculePropose supported
		if (!$query instanceof QueryMoleculePropose) {
			throw new CodeException(static::class.'::createMoleculeQuery - required class instance of QueryMoleculePropose.');
		}

		// Set molecule for the current query
		if ($molecule) {
			$query->setMolecule($molecule);
		}

		return $query;
	}

	/**
	 * @param $code
	 * @param $token
	 * @return Response
	 * @throws \Exception
	 */
	public function getBalance ( $code, $token )
	{
		// Create a query
		$query = $this->createQuery(QueryBalance::class);

		// If bundle hash came, we pass it, otherwise we consider it a secret
		$bundleHash = Wallet::isBundleHash( $code ) ? $code : Crypto::generateBundleHash( $code );

		// Execute the query
		return $query->execute([
			'bundleHash' => $bundleHash,
			'token'      => $token,
		]);
	}


    /**
     * @param $secret
     * @param $token
     * @param $amount
     * @param array $metas
     * @return Response
     * @throws \ReflectionException|\Exception
     */
	public function createToken ( $secret, $token, $amount, array $metas = null )
	{
		$metas = \default_if_null($metas, []);

		// Source wallet
		$sourceWallet = $this->getSourceWallet($secret);

		// Recipient wallet
		$recipientWallet = new Wallet( $secret, $token );
		if (array_get($metas, 'fungibility') === 'stackable') { // For stackable token - create a batch ID
			$recipientWallet->batchId = Wallet::generateBatchId();
		}


		// Create a query
		$query = $this->createMoleculeQuery(QueryTokenCreate::class);

		// Fill a molecule
		$query->fillMolecule ( $secret, $sourceWallet, $recipientWallet, $amount, $metas );
		$this->setSourceWallet( $secret, $query->remainderWallet() );

		// Return a query execution result
		return $query->execute ();
	}


	/**
	 * @param $secret
	 * @param $token
	 * @param $value
	 * @param $to wallet address OR bundle
	 * @param array|null $metas
	 * @return mixed
	 * @throws \Exception
	 */
	public function receiveToken ( $secret, $token, $value, $to, array $metas = null )
	{
		$metas = \default_if_null($metas, []);

		// Source wallet
		$sourceWallet = new Wallet($secret);

		// Meta type
		$metaType = Wallet::isBundleHash( $to ) ? 'walletbundle' : 'wallet';

		// Create a query
		$query = $this->createMoleculeQuery(QueryTokenReceive::class);

		// Fill a molecule
		$query->fillMolecule ( $secret, $sourceWallet, $token, $value, $metaType, $to, $metas );

		// Return a query execution result
		return $query->execute ();
	}


	/**
	 * @param Wallet $sourceWallet
	 * @param string $bundleHash
	 * @param string $type
	 * @param string $code
	 * @return Response
	 * @throws \ReflectionException|\Exception
	 */
	public function createIdentifier ($secret, $type, $contact, $code)
	{
		// Create source & remainder wallets
		$sourceWallet = $this->getSourceWallet($secret);

		// Create & execute a query
		$query = $this->createMoleculeQuery(QueryIdentifierCreate::class);

		// Fill a molecule
		$query->fillMolecule ( $secret, $sourceWallet, $type, $contact, $code);
		$this->setSourceWallet( $secret, $query->remainderWallet() );

		// Execute a query
		return $query->execute();
	}


    /**
     * Bind shadow wallet
     *
     * @param $bundleHash
     * @param $token
     * @throws \Exception
     */
	public function claimShadowWallet ($secret, $token, Wallet $sourceWallet = null) {

		// Source wallet
		$sourceWallet = \default_if_null($sourceWallet, $this->getSourceWallet($secret) );

		// Get shadow wallet list
		$query = $this->createQuery(QueryWalletList::class);
		$response = $query->execute([
			'bundleHash'	=> Crypto::generateBundleHash($secret),
			'token'			=> $token,
		]);
		$shadowWallets = $response->payload();

		// Shadow wallet
		if (!$shadowWallets) {
			throw new WalletShadowException();
		}


		// Create a query
		$query = $this->createMoleculeQuery(QueryShadowWalletClaim::class);

		// Fill a molecule
		$query->fillMolecule($secret, $sourceWallet, $shadowWallets);
		$this->setSourceWallet( $secret, $query->remainderWallet() );

		// Execute a query
		return $query->execute();
	}


	/**
	 * @param string $fromSecret
	 * @param string|Wallet $to
	 * @param string $token
	 * @param int|float $amount
	 * @return array
	 * @throws \Exception|\ReflectionException|InvalidResponseException
	 */
	public function transferToken ( $fromSecret, $to, $token, $amount, Wallet $remainderWallet = null)
	{
		// Get a from wallet
		$fromWallet = $this->getBalance( $fromSecret, $token )->payload();
		if ( $fromWallet === null || Decimal::cmp($fromWallet->balance, $amount) < 0) {
			throw new TransferBalanceException('The transfer amount cannot be greater than the sender\'s balance');
		}


		// If this wallet is assigned, if not, try to get a valid wallet
		$toWallet = $to instanceof Wallet ? $to : $this->getBalance( $to, $token )->payload();

		// Has not wallet yet - create it
		if ($toWallet === null) {
			$toWallet = Wallet::create($to, $token);
		}

		// Batch ID initialization
		$toWallet->initBatchId($fromWallet, $amount);



		// Create a query
		$query = $this->createMoleculeQuery(QueryTokenTransfer::class);

		// Fill a molecule
		$query->fillMolecule ( $fromSecret, $fromWallet, $toWallet, $token, $amount, $remainderWallet );

		// Execute a query
		return $query->execute();
	}


}
