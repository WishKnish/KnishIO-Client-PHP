<?php

namespace WishKnish\KnishIO\Client\Tests;


use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Query\QueryPeerCreate;
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
		$this->guzzle_client = $this->client($this->source_secret)->client();
	}


	/**
	 * Clear data test
	 *
	 * @throws \ReflectionException
	 */
	public function testClearAll () {

		// Call server cleanup
		$this->callServerCleanup(\WishKnish\KnishIO\Tests\QueryServerTest::class);

		// Initial code
		$this->beforeExecute();

		// Deafult assertion
		$this->assertEquals(true, true);
	}


	/**
	 * @throws \ReflectionException
	 */
	public function testMetaIsotope () {

		// Call server cleanup
		$this->callServerCleanup(\WishKnish\KnishIO\Tests\QueryServerTest::class);

		$this->beforeExecute();

		// Create a meta molecule
		$molecule = $this->client($this->source_secret)->createMolecule();
		$molecule->initMeta(
			['key1' => 'value1', 'key2' => 'value2'],
			'metaType',
			'metaId'
		);
		$molecule->sign();
		$molecule->check();

		// Execute query & check response
		$this->executeMolecule( $this->source_secret, $molecule );
	}


	/**
	 * @throws \ReflectionException
	 */
	public function testMetaWalletBundle () {

		$this->assertEquals( true, true );
		return;

		$this->beforeExecute();

		// Meta & encryption
		$meta = ['key1' => 'value1', 'key2' => 'value2'];

		$server_secret = env('SECRET_TOKEN_KNISH');
		$server_wallet = $this->client($server_secret)
			->getContinuId( Crypto::generateBundleHash( $server_secret ) )
			->payload();


		/*
		$server_wallet = new Wallet( $server_secret, 'USER', 'f0d565b50fd40bda4afd128f4daafe77bd6c8561dc3ab5422ecca5e5726054c4');

		dump ($server_wallet->position);
		$value = [
			'6D10LZNmlLs' => 'AGG5pXiVQgnUXsrWopHrOaJENY4DGvQ270NenAAL3LZCW9MELVRSeHZ2aaR7YEhg5lDKvUUF8hqFHubv8CIgb8EMMkqf0ZI7G9Pe2sB3HiUudDa',
			'6m6r0SckeEB' => 'BM1g2kMOvHCUngJcMKK9KFlKPfCTmU9CSgAlJtEGf4Td5cabTOdPGM9lp9o2Ujbgs6pjVYgHHqJTRt4llBhiof036rHWjL4JdcdjlpCTkTAhndt',
		];
		$result = $server_wallet->decryptMyMessage ($value);
		dd ($result);
		*/


		// Create a meta molecule
		$molecule = $this->client($this->source_secret)->createMolecule();
		$molecule->initBundleMeta(
			$molecule->encryptMessage( $meta, [$server_wallet] ),
		);
		$molecule->sign();
		$molecule->check();

		// Execute query & check response
		$this->executeMolecule( $this->source_secret, $molecule );
	}


	/**
	 * @throws \ReflectionException
	 */
	public function testAppendMetaIsotope () {
		$this->beforeExecute();

		// Create a meta molecule
		$molecule = $this->client($this->source_secret)->createMolecule();
		$molecule->initMetaAppend(
			['key2' => 'value2', 'key3' => 'value3'],
			'metaType',
			'metaId'
		);
		$molecule->sign();
		$molecule->check();

		// Execute query & check response
		$this->executeMolecule( $this->source_secret, $molecule );
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
		$molecule = $this->client( $this->source_secret )->createMolecule();
		$molecule->initWalletCreation( $newWallet, new Wallet($this->source_secret) );
		$molecule->sign();

		// Execute query & check response
		$this->executeMolecule( $this->source_secret, $molecule );
	}


	/**
	 * @throws \Exception
	 */
	public function testPeerCreation () {

		$this->beforeExecute();

		// Query
		$query = $this->client( $this->source_secret )
			->createMoleculeQuery( QueryPeerCreate::class );
		$query->fillMolecule( 'testPeerSlug', 'test.peer', 'testPeerName', [ 'cellslug1', 'cellslug2' ] );

		$molecule = $query->execute();

		dd ( $molecule );
	}




}
