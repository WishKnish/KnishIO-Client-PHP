<?php

namespace WishKnish\KnishIO\Client\Tests;

use PHPUnit\Framework\TestCase as StandartTestCase;
use WishKnish\KnishIO\Atom;
use WishKnish\KnishIO\Client\KnishIO;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Query\QueryLinkIdentifierMutation;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Molecule;



/**
 * Class TokenTransactionTest
 * @package WishKnish\KnishIO\Tests
 */
class TokenClientTransactionTest extends StandartTestCase
{

	// Token slugs
	protected $token_slug = [
		'fungible'	=> 'UTFUNGIBLE',
		'stackable'	=> 'UTSTACKABLE',
	];


	/**
	 * Data filepath
	 *
	 * @return string
	 */
	protected function dataFilepath () {
		return class_basename(static::class).'.data';
	}


	/**
	 * Save data
	 *
	 * @param array $data
	 */
	protected function saveData (array $data, $filepath = null) {
		$filepath = $filepath ?? $this->dataFilepath();
		file_put_contents($filepath, \json_encode($data));
	}


	/**
	 * @return mixed
	 */
	protected function getData ($filepath = null) {
		$filepath = $filepath ?? $this->dataFilepath();
		return json_decode(file_get_contents($filepath), true);
	}


	/**
	 * @return mixed
	 */
	protected function clearData ($filepath = null) {
		$filepath = $filepath ?? $this->dataFilepath();
		if (file_exists($filepath) ) {
			unlink($filepath);
		}
	}


	/**
	 * @param array $response
	 */
	protected function checkResponse (Response $response) {
		$data = $response->data();
		if ($data['status'] !== 'accepted') {
			dump ($response);
		}
		$this->assertEquals($data['status'], 'accepted');
	}


	/**
	 * Check wallet
	 * @param string $bundle
	 * @param string $token
	 * @param $amount
	 * @param bool $hasBatchID
	 * @throws \ReflectionException
	 */
	protected function checkWallet (string $bundle, string $token, $amount, bool $hasBatchID = false) {

		// Get a wallet
		$wallet = KnishIO::getBalance($bundle, $token)->payload();

		// Assert wallet's balance
		$this->assertEquals($wallet->balance, $amount);

		// Has a batchID
		if (!$hasBatchID) {
			$this->assertNull($wallet->batchId);
		}
		else {
			$this->assertNotNull($wallet->batchId);
		}
	}

	/**
	 * Check wallet amount
	 *
	 * @param string $bundle
	 * @param int $amount
	 * @throws \ReflectionException
	 */
	protected function checkWalletShadow (string $bundle, string $token, $amount) {
		$this->checkWallet ($bundle, $token, $amount, true);
	}


	/**
	 * Before execute
	 */
	protected function beforeExecute () {

		// Get an app url
		$app_url = env('APP_URL', 'http://dev.wishknish'); // @todo problem with unit test env, APP_URL is null!

		// Check app url
		if (!$app_url) {
			throw new \Exception('APP_URL is empty.');
		}

		// Override url
		KnishIO::setUrl($app_url.'/graphql');
	}


	/**
	 * Clear data test
	 *
	 * @throws \ReflectionException
	 */
	public function testClearAll () {

		// Root path
		$root_path = dirname((new \ReflectionClass(\PHPUnit\TextUI\Command::class))->getFileName()).
			'/../../../../../';

		// Class & filepath
		$class = \WishKnish\KnishIO\Tests\TokenServerTransactionTest::class;
		$filepath = (new \ReflectionClass($class))->getFileName();

		// If a file is exists
		if (file_exists($filepath) ) {

			// Create & run a unit test command
			$command = new \PHPUnit\TextUI\Command();
			$response = $command->run([
				'phpunit',
				'--configuration',
				$root_path.'\phpunit.xml',
				'--filter',
				'/(::testClearAll)( .*)?$/',
				$class,
				$filepath,
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
			'fungible'	=> Crypto::generateSecret(),
			'stackable'	=> Crypto::generateSecret(),
			'recipient'	=> [
				Crypto::generateSecret(),
				Crypto::generateSecret(),
				Crypto::generateSecret(),
				Crypto::generateSecret(),
			],
		];

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
	 * @throws \ReflectionException
	 */
	public function testBaseTransaction () {

		// Initial code
		$this->beforeExecute ();


		// Data
		$data = $this->getData();
		$token = $this->token_slug['fungible'];
		$from_secret = array_get($data, 'secret.fungible');
		$transaction_amount = array_get($data, 'amount.transaction');
		$full_amount = array_get($data, 'amount.full');


		// Secrets initialization
		$secret = array_get($this->getData(), 'secret');

		// Get to bundle hashes from the recipient secret
		$toSecret = array_get($data, 'secret.recipient');



		// --- Batch transfer (splitting)
		$response = KnishIO::transferToken($from_secret, $toSecret[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($toSecret[0], $token, $transaction_amount);

		// --- Batch transfer second transaction (the amount of shadow wallet will be incrementing with a new one)
		$response = KnishIO::transferToken($from_secret, $toSecret[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($toSecret[0], $token, $transaction_amount * 2);



		// --- Batch transfer to other recipient
		$response = KnishIO::transferToken($from_secret, $toSecret[1], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($toSecret[1], $token, $transaction_amount);



		// --- Batch 1-st transfer
		$response = KnishIO::transferToken($from_secret, $toSecret[2], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($toSecret[2], $token, $transaction_amount);

		// --- Batch lastd transfer
		$remainder_amount = $full_amount - $transaction_amount*4;
		$response = KnishIO::transferToken($from_secret, $toSecret[2], $token, $remainder_amount);
		$this->checkResponse($response);
		$this->checkWallet($toSecret[2], $token, $remainder_amount + $transaction_amount);

		// dump ('END: testBaseSplitTransaction');
	}



	/**
	 * Test token transfering
	 *
	 * @throws \ReflectionException
	 */
	public function testBatchTransaction () {

		// Data
		$data = $this->getData();
		$from_secret = array_get($data, 'secret.stackable');
		$transaction_amount = array_get($data, 'amount.transaction');
		$full_amount = array_get($data, 'amount.full');
		$token = $this->token_slug['stackable'];

		// Initial code
		$this->beforeExecute ();

		// Secrets initialization
		$secret = array_get($this->getData(), 'secret');

		// Get to bundle hashes from the recipient secret
		$toBundle = [];
		foreach (array_get($data, 'secret.recipient') as $secret) {
			$toBundle[] = Crypto::generateBundleHash($secret);
		}

		// --- Batch transfer (splitting)
		$response = KnishIO::transferToken($from_secret, $toBundle[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[0], $token, $transaction_amount);

		// --- Batch transfer second transaction (the amount of shadow wallet will be incrementing with a new one)
		$response = KnishIO::transferToken($from_secret, $toBundle[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[0], $token, $transaction_amount * 2);



		// --- Batch transfer to other recipient
		$response = KnishIO::transferToken($from_secret, $toBundle[1], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[1], $token, $transaction_amount);



		// --- Batch 1-st transfer
		$response = KnishIO::transferToken($from_secret, $toBundle[2], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[2], $token, $transaction_amount);

		// --- Batch last transfer
		$remainder_amount = $full_amount - $transaction_amount * 4;
		$response = KnishIO::transferToken($from_secret, $toBundle[2], $token, $remainder_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[2], $token, $remainder_amount + $transaction_amount);
	}


	/**
	 * Bind shadow wallets
	 */
	public function testClaimShadowWallets () {

		// Initial code
		$this->beforeExecute ();

		// Data
		$recipients	= array_get($this->getData(), 'secret.recipient');
		$token		= $this->token_slug['stackable'];



		// --- Bind a shadow wallet with wrong USER wallet

		// Get a shadow wallet of the recipient.0
		//$shadowWallet = KnishIO::getBalance($recipients[0], $token);

		// Set a claim request with USER wallet recipient.1
		// $response = KnishIO::claimShadowWallet($recipients[1], $token, $shadowWallet);
		// $this->checkResponse ($response);
		// ---


		// Query
		$query = new QueryLinkIdentifierMutation(KnishIO::getClient(), KnishIO::getUrl());
		$response = $query->execute([
			'bundle' => Crypto::generateBundleHash($recipients[0]),
			'type' => 'email',
			'content' => 'test@test.test',
		]);
		dd ($response);

		// --- Bind a shadow wallet
		$response = KnishIO::createIdentifier($recipients[0], $token, 'email', '1234');
		$this->checkResponse ($response);
		// ---


		// --- Bind a shadow wallet
		$response = KnishIO::claimShadowWallet($recipients[0], $token);
		$this->checkResponse ($response);
		// ---
	}


}
