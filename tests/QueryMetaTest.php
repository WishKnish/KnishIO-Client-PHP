<?php

namespace WishKnish\KnishIO\Client\Tests;


use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Query\QueryMetaType;
use WishKnish\KnishIO\Client\Wallet;

use WishKnish\KnishIO\Client\Query\QueryMoleculePropose;


// !!! @todo: this unit test must to be separated from any server side (it should work as an independent part) !!!


/**
 * Class QueryMetaTest
 * @package WishKnish\KnishIO\Client\Tests
 */
class QueryMetaTest extends TestCase
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
			[
				'key1_1' => 'value1_1',
				'key1_2' => 'value1_2',
				'key_shared' => 'value_shared',
			],
			'metaType1',
			'metaId1',
		);
		$molecule->initMeta($this->source_wallet,
			[
				'key1_1' => 'value1_1_last',
				'key1_2' => 'value1_2_last',
				'key_shared' => 'value_shared_last',
			],
			'metaType1',
			'metaId1',
		);

		$molecule->initMeta($this->source_wallet,
			[
				'key2_1' => 'value2_1',
				'key2_2' => 'value2_2',
				'key_shared' => 'value_shared',
			],
			'metaType2',
			'metaId2',
		);
		$molecule->initMeta($this->source_wallet,
			[
				'key2_1' => 'value2_1_last',
				'key2_2' => 'value2_2_last',
				'key_shared' => 'value_shared_last',
			],
			'metaType2',
			'metaId2',
		);

		$molecule->initMeta($this->source_wallet,
			[
				'key3_1' => 'value3_1',
				'key3_2' => 'value3_2',
				'key_shared' => 'value_shared',
			],
			'metaType3',
			'metaId3',
		);
		$molecule->initMeta($this->source_wallet,
			[
				'key3_1' => 'value3_1_last',
				'key3_2' => 'value3_2_last',
				'key_shared' => 'value_shared_last',
			],
			'metaType3',
			'metaId3',
		);

		$molecule->sign($this->source_secret);
		$molecule->check();

		// Execute query & check response
		$this->executeProposeMolecule($molecule);
	}


	/**
	 * @throws \ReflectionException
	 */
	public function testMetaLatest () {

		$this->beforeExecute();

		/*
		metaType: String,
        metaTypes: [ String! ],
        metaId: String,
        metaIds: [ String! ],
        key: String,
        keys: [ String! ],
        value: String,
        values: [ String! ]
    	*/

		// Execute query & check response
		$query = new QueryMetaType($this->guzzle_client);
		$response = $query->execute([
			'metaType' => 'metaType1',
		]);
		dd ($response->data());
		$this->checkResponse ($response);
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
