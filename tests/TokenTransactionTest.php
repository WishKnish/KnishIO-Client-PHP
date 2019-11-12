<?php

namespace WishKnish\KnishIO\Client\Tests;

use PHPUnit\Framework\TestCase as StandartTestCase;
use WishKnish\KnishIO\Client\KnishIO;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Molecule;
use WishKnish\KnishIO\Wallet;


/**
 * Class TokenTransactionTest
 * @package WishKnish\KnishIO\Tests
 */
class TokenTransactionTest extends StandartTestCase
{

	// Token slugs
	protected $token_slug = [
		'fungible'	=> 'UTFUNGIBLE',
		'stackable'	=> 'UTSTACKABLE',
	];

	// Data filepath
	protected $data_filepath = 'TokenTransactionTest.data';


	/**
	 * Save data
	 *
	 * @param array $data
	 */
	protected function saveData (array $data, $filepath = null) {
		$filepath = $filepath ?? $this->data_filepath;
		file_put_contents($filepath, \json_encode($data));
	}


	/**
	 * @return mixed
	 */
	protected function getData ($filepath = null) {
		$filepath = $filepath ?? $this->data_filepath;
		return json_decode(file_get_contents($filepath), true);
	}


	/**
	 * @return mixed
	 */
	protected function clearData ($filepath = null) {
		$filepath = $filepath ?? $this->data_filepath;
		if (file_exists($filepath) ) {
			unlink($filepath);
		}
	}


	/**
	 * @param array $response
	 */
	protected function checkResponse (array $response) {
		if ($response['status'] !== 'accepted') {
			dump ($response['reason']);
		}
		$this->assertEquals($response['status'], 'accepted');
	}


	/**
	 * Before execute
	 */
	protected function beforeExecute () {

		// Override url
		KnishIO::setUrl('http://dev.wishknish/graphql');
	}


	/**
	 * Clear data test
	 *
	 * @throws \ReflectionException
	 */
	public function testClearAll () {

		// Base dir
		$base_dir = __DIR__.'/../../../../';

		// Check is a clear file exists
		$knishio_clear_testfile = $base_dir.'\vendor\wishknish\knishio\tests\TokenTransactionTest.php';
		if (file_exists($knishio_clear_testfile) ) {

			// Create & run a unit test command
			$command = new \PHPUnit\TextUI\Command();
			$response = $command->run([
				$base_dir.'/vendor/phpunit/phpunit/phpunit',
				'--configuration',
				$base_dir.'\phpunit.xml',
				'--filter',
				'/(::testClearAll)( .*)?$/',
				'WishKnish\KnishIO\Tests\TokenTransactionTest',
				$knishio_clear_testfile,
				'--teamcity',
			], false);
		}
		$this->assertEquals(true, true);
	}


	/**
 * Test create token
 *
 * @throws \ReflectionException
 */
	public function testCreateToken () {

		// Initial code
		$this->beforeExecute ();


		// Full amount
		$full_amount = 1000;

		// Secret array
		$secret = [
			'fungible'	=> Crypto::generateSecret(null, 2048),
			'stackable'	=> Crypto::generateSecret(null, 2048),
			'recipient'	=> [
				Crypto::generateSecret(null, 2048),
				Crypto::generateSecret(null, 2048),
				Crypto::generateSecret(null, 2048),
			],
		];


		/*

		// --- Create a non-stackable token
		$tokenMeta = [
			'name'			=> $this->token_slug['fungible'],
			'fungibility'	=> 'fungible',
			'splittable'	=> 0,
			'supply'		=> 'limited',
			'decimals'		=> 0,
			'icon'			=> 'icon',
		];
		$response = KnishIO::createToken($secret['fungible'], $this->token_slug['fungible'], $full_amount, $tokenMeta);
		$this->checkResponse($response);

		*/


		// --- Create a stackable token
		$tokenMeta = [
			'name'			=> $this->token_slug['stackable'],
			'fungibility'	=> 'stackable',
			'splittable'	=> 1,
			'supply'		=> 'limited',
			'decimals'		=> 0,
			'icon'			=> 'icon',
		];
		$response = KnishIO::createToken($secret['stackable'], $this->token_slug['stackable'], $full_amount, $tokenMeta);
		$this->checkResponse($response);


		// Save data
		$this->saveData (['secret' => $secret, 'amount' => [
			'full'			=> $full_amount,
			'transaction'	=> 100,
		]]);
	}




	/**
	 * Test token transfering
	 *
	 * @throws \ReflectionException
	 */
	public function testBatchSplitTransaction () {

		// Data
		$data = $this->getData();
		$from_secret = array_get($data, 'secret.stackable');
		$transaction_amount = array_get($data, 'amount.transaction');
		$full_amount = array_get($data, 'amount.full');

		// Initial code
		$this->beforeExecute ();

		// Secrets initialization
		$secret = array_get($this->getData(), 'secret');

		// Get to bundle hashes from the recipient secret
		$toBundle = Crypto::generateBundleHash(array_get($data, 'secret.recipient.0'));

		// --- Batch transfer (splitting)
		$response = KnishIO::splitToken($from_secret, $toBundle, $this->token_slug['stackable'], $transaction_amount);
		$this->checkResponse($response);

		// --- Batch transfer second transaction (the amount of shadow wallet will be incrementing with a new one)
		$response = KnishIO::splitToken($from_secret, $toBundle, $this->token_slug['stackable'], $transaction_amount);
		$this->checkResponse($response);
	}


	/**
	 * Test token transfering
	 *
	 * @throws \ReflectionException
	 */
	public function testBatchFullTransaction () {

		return;

		// Initial code
		$this->beforeExecute ();

		// Secrets initialization
		$secret = array_get($this->getData(), 'secret');

		// Get to bundle hashes from the recipient secret
		$toSecret = Crypto::generateSecret(null, 2048);
		$toBundle = Crypto::generateBundleHash($toSecret);

		// Get a from wallet to get it balance
		$fromWallet = KnishIO::getBalance( $secret['stackable'], $this->token_slug['stackable'] );

		// --- Batch transfer (splitting)
		$response = KnishIO::splitToken($secret['stackable'], $toBundle, $this->token_slug['stackable'], $fromWallet->balance);
		$this->checkResponse($response);
	}



}
