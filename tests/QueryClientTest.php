<?php

namespace WishKnish\KnishIO\Client\Tests;


use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;

use WishKnish\KnishIO\Client\Query\QueryMoleculePropose;


// !!! @todo: this unit test must to be separated from any server side (it should work as an independent part) !!!


/**
 * Class QueryClientTest
 * @package WishKnish\KnishIO\Client\Tests
 */
class QueryClientTest extends TestCase
{
	protected $source_secret;
	protected $source_wallet;

	protected $guzzle_client;


	/**
	 * @throws \Exception
	 */
	public function beforeExecute()
	{
		parent::beforeExecute();

		// Source secret & wallet
		$this->source_secret = Crypto::generateSecret();
		$this->source_wallet = new Wallet ($this->source_secret);

		// Guzzle client from the KnishIOClient object
		$this->guzzle_client = $this->client->client();
	}


	/**
	 * Clear data test
	 *
	 * @throws \ReflectionException
	 */
	public function testClearAll () {

		// Initial code
		$this->beforeExecute();

		// Call server cleanup
		$this->callServerCleanup(\WishKnish\KnishIO\Tests\QueryServerTest::class);

		// Deafult assertion
		$this->assertEquals(true, true);
	}


	/**
	 * @throws \ReflectionException
	 */
	public function testMetaIsotope () {

		$this->beforeExecute();

		// Create a meta molecule
		$molecule = new Molecule();
		$molecule->initMeta($this->source_wallet,
			['key1' => 'value1', 'key2' => 'value2'],
			'metaType',
			'metaId'
		);
		$molecule->sign($this->source_secret);
		$molecule->check();

		// Execute query & check response
		$this->executeProposeMolecule($molecule);
	}


	/**
	 * @throws \ReflectionException
	 */
	public function testAppendMetaIsotope () {
		$this->beforeExecute();

		// Create a meta molecule
		$molecule = new Molecule();
		$molecule->initMetaAppend($this->source_wallet,
			['key2' => 'value2', 'key3' => 'value3'],
			'metaType',
			'metaId'
		);
		$molecule->sign($this->source_secret);
		$molecule->check();

		// Execute query & check response
		$this->executeProposeMolecule($molecule);
	}


	/**
	 * @throws \ReflectionException
	 */
	public function testWalletCreation () {

		$this->beforeExecute();

		// New wallet
		$new_wallet_secret = Crypto::generateSecret();
		$newWallet = new Wallet($new_wallet_secret, 'UTINITWALLET');

		// Create a molecule
		$molecule = new Molecule();
		$molecule->initWalletCreation($this->source_wallet, $newWallet, new Wallet($this->source_secret));
		$molecule->sign($this->source_secret);

		// Execute query & check response
		$this->executeProposeMolecule($molecule);
	}


	/**
	 * @param $molecule
	 */
	protected function executeProposeMolecule ($molecule) {

		// Execute query & check response
		$query = new QueryMoleculePropose($this->guzzle_client);
		$response = $query->execute(['molecule' => $molecule]);
		$this->checkResponse ($response);
	}

}
