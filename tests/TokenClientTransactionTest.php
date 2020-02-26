<?php

namespace WishKnish\KnishIO\Client\Tests;

use Dotenv\Dotenv;
use WishKnish\KnishIO\Atom;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Decimal;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\Query\QueryLinkIdentifierMutation;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Molecule;


// !!! @todo: this unit test must to be separated from any server side (it should work as an independent part) !!!



/**
 * Class TokenTransactionTest
 * @package WishKnish\KnishIO\Tests
 */
class TokenClientTransactionTest extends TestCase
{

	// Token slugs
	protected $token_slug = [
		'fungible'	=> 'UTFUNGIBLE',
		'stackable'	=> 'UTSTACKABLE',
		'env.fungible' => 'UTENVFUNGIBLE',
		'env.stackable' => 'UTENVSTACKABLE',
	];


	/**
	 * Check wallet
	 * @param string $bundle
	 * @param string $token
	 * @param $amount
	 * @param bool $hasBatchID
	 * @throws \ReflectionException
	 */
	protected function checkWallet ($bundle, $token, $amount, $hasBatchID = false) {

		// Get a wallet
		$response = $this->client->getBalance($bundle, $token);
		if (!$wallet = $response->payload() ) {
			$this->debug ($response, true);
		}

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
	protected function checkWalletShadow ($bundle, $token, $amount, $hasBatchId) {
		$this->checkWallet ($bundle, $token, $amount, $hasBatchId);
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
		$this->callServerCleanup(\WishKnish\KnishIO\Tests\TokenServerTransactionTest::class);

		// Deafult assertion
		$this->assertEquals(true, true);
	}


	/**
	 * @throws \Exception
	 */
	public function testCreateToken () {

		// Initial code
		$this->beforeExecute();

		// Full amount
		$full_amount = 1000.00001; //10000.0 + 10.0/(Decimal::$multiplier);

		$env_secret = env('SECRET_TOKEN_KNISH');
		if (!$env_secret) {
			throw new \Exception('env.SECRET_TOKEN_KNISH is not set.');
		}

		// Secret array
		$secret = [
			'fungible'	=> Crypto::generateSecret(),
			'stackable'	=> Crypto::generateSecret(),
			'env' => $env_secret,
			'recipient'	=> [
				Crypto::generateSecret(),
				Crypto::generateSecret(),
				Crypto::generateSecret(),
				Crypto::generateSecret(),
			],
			'receivers' => [
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
		$response = $this->client->createToken($secret['fungible'], $this->token_slug['fungible'], $full_amount, $tokenMeta);
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
		$response = $this->client->createToken($secret['stackable'], $this->token_slug['stackable'], $full_amount, $tokenMeta);
		$this->checkResponse($response);



		// --- Create a ENV non-stackable & stackable tokens
		// ... non-stackable
		$tokenMeta = [
			'name'			=> $this->token_slug['env.fungible'],
			'fungibility'	=> 'fungible',
			'splittable'	=> 0,
			'supply'		=> 'limited',
			'decimals'		=> 0,
			'icon'			=> 'icon',
		];
		$response = $this->client->createToken($secret['env'], $this->token_slug['env.fungible'], $full_amount, $tokenMeta);
		$this->checkResponse($response);
		// ... stackable token
		$tokenMeta = [
			'name'			=> $this->token_slug['env.stackable'],
			'fungibility'	=> 'stackable',
			'splittable'	=> 1,
			'supply'		=> 'limited',
			'decimals'		=> 0,
			'icon'			=> 'icon',
		];
		$response = $this->client->createToken($secret['env'], $this->token_slug['env.stackable'], $full_amount, $tokenMeta);
		$this->checkResponse($response);



		// Save data
		$this->saveData (['secret' => $secret, 'amount' => [
			'full'			=> $full_amount,
			'transaction'	=> 100.000001, //1000.0 + 1.0/Decimal::$multiplier,
		]]);
	}


	/**
	 * @throws \ReflectionException
	 */
	public function testReceiveToken () {

		// Initial code
		$this->beforeExecute ();

		// Data
		$data = $this->getData();
		$transaction_amount = array_get($data, 'amount.transaction');
		$full_amount = array_get($data, 'amount.full');

		// Secrets initialization
		$secret = array_get($this->getData(), 'secret');

		// Receivers
		$receivers = array_get($data, 'secret.receivers');

		// Get to bundle hashes from the recipient secret
		$toBundle = [];
		foreach ($receivers as $receiver_secret) {
			$toBundle[] = Crypto::generateBundleHash($receiver_secret);
		}



		// ------------ FUNGIBLE ----------------

		// Set token as FUNGIBLE
		$token = $this->token_slug['env.fungible'];

		// --- RECEIVER.0

		// --- Base receive (NOT-splitting)
		$response = $this->client->receiveToken($secret['env'], $token, $transaction_amount, $toBundle[0]);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[0], $token, $transaction_amount * 1.0, false);

		// --- Base receive (NOT-splitting)
		$response = $this->client->receiveToken($secret['env'], $token, $transaction_amount, $toBundle[0]);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[0], $token, $transaction_amount * 2.0, false);

		// --- RECEIVER.1

		// --- Base receive (NOT-splitting)
		$response = $this->client->receiveToken($secret['env'], $token, $transaction_amount, $toBundle[1]);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[1], $token, $transaction_amount * 1.0, false);

		// Claim shadow wallet
		$this->claimShadowWallet ($token, $receivers[0], [$receivers[1]]);



		// ------------ STACKABLE ----------------

		// Set token as STACKABLE
		$token = $this->token_slug['env.stackable'];

		// --- Batch receive (splitting)
		$response = $this->client->receiveToken($secret['env'], $token, $transaction_amount, $toBundle[0]);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[0], $token, $transaction_amount * 1.0, true);

		// --- Batch receive (splitting)
		$response = $this->client->receiveToken($secret['env'], $token, $transaction_amount, $toBundle[0]);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[0], $token, $transaction_amount * 1.0, true);

		// --- Batch receive (splitting) WITHOUT a remainder
		$remainder_amount = ($full_amount - $transaction_amount * 2.0);
		$response = $this->client->receiveToken($secret['env'], $token, $remainder_amount, $toBundle[0]);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[0], $token, $remainder_amount, true);
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
		$response = $this->client->transferToken($from_secret, $toSecret[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($toSecret[0], $token, $transaction_amount);

		// --- Batch transfer second transaction (the amount of shadow wallet will be incrementing with a new one)
		$response = $this->client->transferToken($from_secret, $toSecret[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($toSecret[0], $token, $transaction_amount * 2.0);



		// --- Batch transfer to other recipient
		$response = $this->client->transferToken($from_secret, $toSecret[1], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($toSecret[1], $token, $transaction_amount);



		// --- Batch 1-st transfer
		$response = $this->client->transferToken($from_secret, $toSecret[2], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($toSecret[2], $token, $transaction_amount);

		// --- Batch last transfer
		$remainder_amount = $full_amount - $transaction_amount*4.0;
		$response = $this->client->transferToken($from_secret, $toSecret[2], $token, $remainder_amount);
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
		$response = $this->client->transferToken($from_secret, $toBundle[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[0], $token, $transaction_amount, true);

		// --- Batch transfer second transaction (the amount of shadow wallet will be incrementing with a new one)
		$response = $this->client->transferToken($from_secret, $toBundle[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[0], $token, $transaction_amount, true);



		// --- Batch transfer to other recipient
		$response = $this->client->transferToken($from_secret, $toBundle[1], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[1], $token, $transaction_amount, true);



		// --- Batch 1-st transfer
		$response = $this->client->transferToken($from_secret, $toBundle[2], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[2], $token, $transaction_amount, true);

		// --- Batch last transfer
		$remainder_amount = $full_amount - $transaction_amount * 4.0;
		$response = $this->client->transferToken($from_secret, $toBundle[2], $token, $remainder_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[2], $token, $remainder_amount, true);
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

		// Claim shadow wallet
		$this->claimShadowWallet ($token, $recipients[0], [$recipients[1]]);
	}


	/**
	 * Claim shadow wallet
	 *
	 * @param array $recipients: 0-indexed recipient is original recipient (right), > 0-indexed recipient is wrong
	 * @throws \Exception
	 */
	protected function claimShadowWallet ($token, $recipient, array $intruders = [])
	{
		// Check
		if (!is_dir(getenv('SERVER_LOG_PATH')) ) {
			throw new \Exception("
				SERVER_LOG_PATH is required in .env file.\r\n
				The path must be to the SERVER storage log and SERVER must have this env: MAIL_DRIVER=log
			");
		}

		// Bundle hash
		$bundleHash = Crypto::generateBundleHash($recipient);
		$email = Strings::randomString(10).'@test.test';

		// Query
		$query = $this->client->createQuery(QueryLinkIdentifierMutation::class);
		$response = $query->execute([
			'bundle'	=> $bundleHash,
			'type'		=> 'email',
			'content'	=> $email,
		]);
		if (!$response->success() ) {
			$this->debug ($response, true);
		}

		// Get a verification code
		$code = $this->getVerificationCode();
		$this->output ([
			'Identifier creating...',
			'Bundle hash: '. $bundleHash,
			'Email: '. $email,
			'Verification code: '. $code
		]);


		// --- Bind a shadow wallet
		$id_response = $this->client->createIdentifier($recipient, 'email', $email, $code);
		$this->checkResponse ($id_response);
		// ---

		// Add recipent to intruders array to check than old recipient wallet is not acceptable
		$intruders[] = $recipient;

		// --- Bind a shadow wallet (with wrong bundle hash)
		foreach ($intruders as $intruder) {
			$response = $this->client->claimShadowWallet($intruder, $token);
			$this->assertEquals($response->status(), 'rejected');
			$this->assertNotEquals(strpos($response->reason(), 'ContinueID verification failure'), false);
		}

		// --- Bind a shadow wallet (with original bundle hash)
		$response = $this->client->claimShadowWallet($recipient, $token, $id_response->query()->remainderWallet());
		$this->checkResponse ($response);
		// ---

	}


	// @todo test function, thinking about real implemenation
	protected function getVerificationCode ($clear_log_file = true) {

		// Root path
		$log_file_pattern = getenv('SERVER_LOG_PATH').'*.log';
		$log_files = glob($log_file_pattern);

		// Get last modified log file
		$log_files = array_combine($log_files, array_map("filemtime", $log_files));
		arsort($log_files);
		$log_file = key($log_files);

		if (!file_exists($log_file) ) {
			throw new \Exception('Log file does not exist.');
		}
		$logs = file_get_contents($log_file);
		if (!preg_match('#<p>Your verification code: <b>([A-Za-z0-9]+)</b></p>#Usi', $logs, $matches) ) {

			throw new \Exception('Identifier code does not exist.');
		}

		// Remove log file
		if ($clear_log_file) {
			unlink($log_file);
		}

		return $matches[1];
	}






}
