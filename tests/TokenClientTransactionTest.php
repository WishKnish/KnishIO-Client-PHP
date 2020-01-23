<?php

namespace WishKnish\KnishIO\Client\Tests;

use Dotenv\Dotenv;
use WishKnish\KnishIO\Atom;
use WishKnish\KnishIO\Client\KnishIO;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Decimal;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\Query\QueryLinkIdentifierMutation;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Molecule;



// !!! @todo: this unit test must to be separated from any server side (it should work as an independent part) !!!


// Supporing variety versions of PHPUnit
if (!class_exists('\PHPUnit_Framework_TestCase') ) {
	abstract class TestCaseBase extends \PHPUnit\Framework\TestCase {}
}
else {
	abstract class TestCaseBase extends \PHPUnit_Framework_TestCase {}
}


/**
 * Class TokenTransactionTest
 * @package WishKnish\KnishIO\Tests
 */
class TokenClientTransactionTest extends TestCaseBase
{
	private $client;
	private $dotenv;


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
		return __DIR__.'/'.substr(strrchr(static::class, "\\"), 1).'.data';
	}


	/**
	 * Save data
	 *
	 * @param array $data
	 */
	protected function saveData (array $data, $filepath = null) {
		$filepath = \default_if_null($filepath, $this->dataFilepath() );
		file_put_contents($filepath, \json_encode($data));
	}


	/**
	 * @return mixed
	 */
	protected function getData ($filepath = null) {
		$filepath = \default_if_null($filepath, $this->dataFilepath() );
		return json_decode(file_get_contents($filepath), true);
	}


	/**
	 * @return mixed
	 */
	protected function clearData ($filepath = null) {
		$filepath = \default_if_null($filepath, $this->dataFilepath() );
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
			$this->debug ($response);
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
	protected function checkWalletShadow ($bundle, $token, $amount) {
		$this->checkWallet ($bundle, $token, $amount, true);
	}


	/**
	 * Before execute
	 *
	 * @throws \Exception
	 */
	protected function beforeExecute () {

		// Load env
		$env_path = __DIR__.'/../';
		$env_file = implode('.', array_filter(['.env', getenv('APP_ENV')]));
		if (is_dir($env_path) ) {

			// Switch between dotenv versions
			if (method_exists('\Dotenv\Dotenv','createImmutable') ) {
				$this->dotenv = \Dotenv\Dotenv::createImmutable($env_path, $env_file);
			}
			else {
				$this->dotenv = \Dotenv\Dotenv::create($env_path, $env_file);
			}

			$this->dotenv->load();
		}

		// Get an app url
		$app_url = getenv('APP_URL');

		// Check app url
		if (!$app_url) {
			throw new \Exception('APP_URL is empty.');
		}

		// Client initialization
		$this->client = new KnishIOClient($app_url.'graphql');
	}



	public function testMetaAggregate () {

		$this->assertEquals(true, true);
	}



	/**
	 * Clear data test
	 *
	 * @throws \ReflectionException
	 */
	public function testClearAll () {

		// PHP version
		$this->output (['PHP Version: '.PHP_VERSION]);

		// PHP version comparing
		if (version_compare(PHP_VERSION, '7.0.0') <= 0) {
			$this->output ([
				'PHP version is less than 7.0.0. Skip "testClearAll" test.',
				'  -- DB must be cleaned manually with all data related to '.implode('", "', $this->token_slug).' tokens.',
				'  -- OR should call \WishKnish\KnishIO\Tests\TokenServerTransactionTest::testClearAll server unit test instead.',
			]);
			return;
		}

		// Before execute
		$this->beforeExecute();

		// Server test filepath
		$server_test_filepath = getenv('SERVER_TEST_PATH').'TokenServerTransactionTest.php';

		// File does not exist
		if (!$server_test_filepath || !file_exists($server_test_filepath) ) {
			print_r("SERVER_TEST_FILE is not defined. Test do not clean up.\r\n");
		}
		else {

			// Class & filepath
			$class = \WishKnish\KnishIO\Tests\TokenServerTransactionTest::class;

			// Create & run a unit test command
			$command = new \PHPUnit\TextUI\Command();
			$response = $command->run([
				'phpunit',
				'--configuration',
				__DIR__.'/../' .'\phpunit.xml',
				'--filter',
				'/(::testClearAll)( .*)?$/',
				$class,
				$server_test_filepath,
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
		$this->beforeExecute();

		// Full amount
		$full_amount = 10.0/(Decimal::$multiplier);

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

		// Save data
		$this->saveData (['secret' => $secret, 'amount' => [
			'full'			=> $full_amount,
			'transaction'	=> 1.0/Decimal::$multiplier,
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
		$this->checkWalletShadow($toBundle[0], $token, $transaction_amount);

		// --- Batch transfer second transaction (the amount of shadow wallet will be incrementing with a new one)
		$response = $this->client->transferToken($from_secret, $toBundle[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[0], $token, $transaction_amount * 2.0);



		// --- Batch transfer to other recipient
		$response = $this->client->transferToken($from_secret, $toBundle[1], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[1], $token, $transaction_amount);



		// --- Batch 1-st transfer
		$response = $this->client->transferToken($from_secret, $toBundle[2], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[2], $token, $transaction_amount);

		// --- Batch last transfer
		$remainder_amount = $full_amount - $transaction_amount * 4.0;
		$response = $this->client->transferToken($from_secret, $toBundle[2], $token, $remainder_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($toBundle[2], $token, $remainder_amount + $transaction_amount);
	}


	/**
	 * Bind shadow wallets
	 */
	public function testClaimShadowWallets () {

		// Initial code
		$this->beforeExecute ();

		// Check
		if (!is_dir(getenv('SERVER_LOG_PATH')) ) {
			throw new \Exception("
				SERVER_LOG_PATH is required in .env file.\r\n
				The path must be to the SERVER storage log and SERVER must have this env: MAIL_DRIVER=log
			");
		}

		// Data
		$recipients	= array_get($this->getData(), 'secret.recipient');
		$token		= $this->token_slug['stackable'];


		// Bundle hash
		$bundleHash = Crypto::generateBundleHash($recipients[0]);
		$email = Strings::randomString(10).'@test.test';

		// Query
		$query = $this->client->createQuery(QueryLinkIdentifierMutation::class);
		$response = $query->execute([
			'bundle'	=> $bundleHash,
			'type'		=> 'email',
			'content'	=> $email,
		]);
		if (!$response->success() ) {
			dd ($response->message());
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
		$id_response = $this->client->createIdentifier($recipients[0], 'email', $email, $code);
		$this->checkResponse ($id_response);
		// ---


		// --- Bind a shadow wallet (with wrong bundle hash)
		$response = $this->client->claimShadowWallet($recipients[1], $token, new Wallet($recipients[1]));
		$this->assertEquals($response->data()['status'], 'rejected');
		$this->assertEquals($response->data()['reason'], 'ShadowWalletHandler::init(): ContinueID check failure.');
		// ---

		// --- Bind a shadow wallet (with original bundle hash)
		$response = $this->client->claimShadowWallet($recipients[0], $token, $id_response->query()->remainderWallet());
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


	/**
	 * Output
	 *
	 * @param array $info
	 */
	protected function output (array $info) {
		echo implode("\r\n", $info)."\r\n\r\n";
	}


	/**
	 * @param Response $response
	 * @param bool $final
	 */
	protected function debug (Response $response, $final = false) {

		// Debug output
		$output = [
			'query' => get_class($response->query()),
			'url' => $response->query()->url(),
		];

		// Reason data on the top of the output
		if (array_has($response->data(), 'reason') ) {
			$output['reason'] = array_get($response->data(), 'reason');
			$output['reasonPayload'] = array_get($response->data(), 'reasonPayload');
		}

		// Other debug info
		$output = array_merge ($output, [
			'variables' => $response->query()->variables(),
			'response' => $response->response(),
		]);

		print_r($output);
		if ($final) {
			die ();
		}
	}

}
