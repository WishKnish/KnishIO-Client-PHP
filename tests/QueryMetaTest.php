<?php

namespace WishKnish\KnishIO\Client\Tests;


use Illuminate\Support\Arr;
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

	public function sourceWallet () {
		return new Wallet ($this->source_secret);
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

		// metaType1
		$this->createMetas ('metaType1', 'metaId1', [
			[
				'key1_1' => 'value1_1',
				'key1_2' => 'value1_2',
				'key_shared' => 'value_shared',
			],
		]);
		$this->createMetas ('metaType1', 'metaId1', [
			[
				'key1_1' => 'value1_1_last',
				'key1_2' => 'value1_2_last',
				'key_shared' => 'value_shared_last',
			],
		]);

		// metaType2
		$this->createMetas ('metaType2', 'metaId2', [
			[
				'key2_1' => 'value2_1',
				'key2_2' => 'value2_2',
				'key_shared' => 'value_shared',
			],
		]);
		$this->createMetas ('metaType2', 'metaId2', [
			[
				'key2_1' => 'value2_1_last',
				'key2_2' => 'value2_2_last',
				'key_shared' => 'value_shared_last',
			],
		]);

		// metaType3
		$this->createMetas ('metaType3', 'metaId3', [
			[
				'key3_1' => 'value3_1',
				'key3_2' => 'value3_2',
				'key_shared' => 'value_shared',
			],
		]);
		$this->createMetas ('metaType3', 'metaId3', [
			[
				'key3_1' => 'value3_1_last',
				'key3_2' => 'value3_2_last',
				'key_shared' => 'value_shared_last',
			],
		]);
	}


	/**
	 * Create metas
	 *
	 * @param $meta_type
	 * @param $meta_id
	 * @param $metas
	 * @throws \ReflectionException
	 */
	protected function createMetas ($meta_type, $meta_id, $metas_array)
	{
		$molecule = new Molecule();
		foreach ($metas_array as $metas) {
			$molecule->initMeta(new Wallet ($this->source_secret), $metas, $meta_type, $meta_id);
		}
		$molecule->sign($this->source_secret);
		$molecule->check();
		$this->executeProposeMolecule($molecule);
	}


	/**
	 * @throws \ReflectionException
	 */
	public function testMetaLatest () {

		$this->beforeExecute();

		// Execute query & check response
		$query = new QueryMetaType($this->guzzle_client);






		// ---------------- META TYPE

		// --- metaType = metaType1
		$response = $query->execute([
			'metaType' => 'metaType1',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType1',
				'instances' => [
					['metaType' => 'metaType1', 'metaId' => 'metaId1'],
				],
			],
		]);


		// --- metaTypes IN [metaType1, metaType2]
		$response = $query->execute([
			'metaTypes' => ['metaType2', 'metaType3'],
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType2',
				'instances' => [
					['metaType' => 'metaType2', 'metaId' => 'metaId2'],
				],
			],
			[
				'metaType' => 'metaType3',
				'instances' => [
					['metaType' => 'metaType3', 'metaId' => 'metaId3'],
				],
			]
		]);


		// --- metaType = metaType4
		$response = $query->execute([
			'metaType' => 'metaType4',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), []);






		// ---------------- META ID


		// --- metaId = metaId1
		$response = $query->execute([
			'metaId' => 'metaId1',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType1',
				'instances' => [
					['metaType' => 'metaType1', 'metaId' => 'metaId1'],
				],
			],
		]);


		// --- metaIds IN [metaId2, metaId3]
		$response = $query->execute([
			'metaIds' => ['metaId2', 'metaId3'],
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType2',
				'instances' => [
					['metaType' => 'metaType2', 'metaId' => 'metaId2'],
				],
			],
			[
				'metaType' => 'metaType3',
				'instances' => [
					['metaType' => 'metaType3', 'metaId' => 'metaId3'],
				],
			]
		]);


		// --- metaId = metaId4
		$response = $query->execute([
			'metaId' => 'metaId4',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), []);





		// ---------------- KEY


		// --- key = key1_1
		$response = $query->execute([
			'key' => 'key1_1',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType1',
				'instances' => [
					['metaType' => 'metaType1', 'metaId' => 'metaId1'],
				],
			],
		]);


		// --- key in [key_1_1, key_3_1]
		$response = $query->execute([
			'keys' => ['key_1_1', 'key_3_1'],
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), []);


		// --- key in [key_1_1, key_3_1]
		$response = $query->execute([
			'keys' => ['key2_1', 'key2_2'],
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType2',
				'instances' => [
					['metaType' => 'metaType2', 'metaId' => 'metaId2'],
				],
			],
		]);


		// --- key = key_shared
		$response = $query->execute([
			'key' => 'key_shared',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType1',
				'instances' => [
					['metaType' => 'metaType1', 'metaId' => 'metaId1'],
				],
			],
			[
				'metaType' => 'metaType2',
				'instances' => [
					['metaType' => 'metaType2', 'metaId' => 'metaId2'],
				],
			],
			[
				'metaType' => 'metaType3',
				'instances' => [
					['metaType' => 'metaType3', 'metaId' => 'metaId3'],
				],
			]
		]);

		// --- key = key_4_1
		$response = $query->execute([
			'key' => 'key_4_1',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), []);




		// ---------------- VALUE


		// --- value = value1_1
		$response = $query->execute([
			'value' => 'value1_1',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType1',
				'instances' => [
					['metaType' => 'metaType1', 'metaId' => 'metaId1'],
				],
			],
		]);

		// --- value = value2_2
		$response = $query->execute([
			'value' => 'value2_2',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType2',
				'instances' => [
					['metaType' => 'metaType2', 'metaId' => 'metaId2'],
				],
			],
		]);

		// --- value = value_shared
		$response = $query->execute([
			'value' => 'value_shared',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType1',
				'instances' => [
					['metaType' => 'metaType1', 'metaId' => 'metaId1'],
				],
			],
			[
				'metaType' => 'metaType2',
				'instances' => [
					['metaType' => 'metaType2', 'metaId' => 'metaId2'],
				],
			],
			[
				'metaType' => 'metaType3',
				'instances' => [
					['metaType' => 'metaType3', 'metaId' => 'metaId3'],
				],
			]
		]);


		// --- values IN [value2_1, value2_2]
		$response = $query->execute([
			'values' => ['value2_1', 'value2_2'],
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType2',
				'instances' => [
					['metaType' => 'metaType2', 'metaId' => 'metaId2'],
				],
			],
		]);


		// --- values IN [value1_1, value2_2]
		$response = $query->execute([
			'values' => ['value2_1', 'value2_2', 'value3_1', 'value3_2'],
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType2',
				'instances' => [
					['metaType' => 'metaType2', 'metaId' => 'metaId2'],
				],
			],
			[
				'metaType' => 'metaType3',
				'instances' => [
					['metaType' => 'metaType3', 'metaId' => 'metaId3'],
				],
			]
		]);

		// --- values = value4_1
		$response = $query->execute([
			'value' => 'value4_1'
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), []);




		// ---------------- KEY & VALUE

		// --- key = key1_1 & value = value1_1
		$response = $query->execute([
			'key' => 'key1_1',
			'value' => 'value1_1',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType1',
				'instances' => [
					['metaType' => 'metaType1', 'metaId' => 'metaId1'],
				],
			],
		]);

		// --- key = key1_1 & value = value1_1_last
		$response = $query->execute([
			'key' => 'key1_1',
			'value' => 'value1_1_last',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType1',
				'instances' => [
					['metaType' => 'metaType1', 'metaId' => 'metaId1'],
				],
			],
		]);

		// --- key = key1_1 & value = value1_2
		$response = $query->execute([
			'key' => 'key1_1',
			'value' => 'value1_2',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), []);


		// --- key = key1_1 & value = value1_2
		$response = $query->execute([
			'key' => 'key_shared',
			'value' => 'value_shared',
		]);
		$this->assertEquals($this->getLimitedResult($response->data()), [
			[
				'metaType' => 'metaType1',
				'instances' => [
					['metaType' => 'metaType1', 'metaId' => 'metaId1'],
				],
			],
			[
				'metaType' => 'metaType2',
				'instances' => [
					['metaType' => 'metaType2', 'metaId' => 'metaId2'],
				],
			],
			[
				'metaType' => 'metaType3',
				'instances' => [
					['metaType' => 'metaType3', 'metaId' => 'metaId3'],
				],
			]
		]);

	}







	/**
	 * @param $data
	 * @return array
	 */
	protected function getLimitedResult ($data, $depth = 2) {
		$result = [];
		foreach ($data as $meta_type) {
			$item = Arr::only($meta_type, ['metaType']);

			if ($depth >= 2) {
				$item['instances'] = [];
				foreach ($meta_type['instances'] as $instance) {
					$metas = $instance['metas'];
					$instance = Arr::only($instance, ['metaType', 'metaId']);

					if ($depth >= 3) {
						$instance['metas'] = [];
						foreach ($metas as $meta) {
							$instance['metas'][] = Arr::only($meta, ['key', 'value']);
						}
					}

					$item['instances'][] = $instance;
				}
			}

			$result[] = $item;
		}
		return $result;
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
