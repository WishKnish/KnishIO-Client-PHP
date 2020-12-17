<?php

namespace WishKnish\KnishIO\Client\Tests;

use Dotenv\Dotenv;
use WishKnish\KnishIO\Atom;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Libraries\Decimal;
use WishKnish\KnishIO\Client\Libraries\Strings;
use WishKnish\KnishIO\Client\Query\QueryLinkIdentifierMutation;
use WishKnish\KnishIO\Client\Mutation\MutationProposeMolecule;
use WishKnish\KnishIO\Client\Query\QueryWalletList;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Molecule;
use WishKnish\KnishIO\Client\Molecule as ClientMolecule;


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


	public function beforeExecute()
	{
		// $this->cell_slug = null;
		// $this->graphql_url = 'https://frontrow.knish.io/graphql';

		parent::beforeExecute();
	}


	/**
	 * Check wallet
	 * @param string $bundle
	 * @param string $token
	 * @param $amount
	 * @param bool $hasBatchID
	 * @throws \ReflectionException
	 */
	protected function checkWallet ($client, $bundle, $token, $amount, $hasBatchID = false) {

		// Get a wallet
		$response = $client->queryBalance($token, $bundle);
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
	protected function checkWalletShadow ($client, $bundle, $token, $amount, $hasBatchId) {
		$this->checkWallet ($client, $bundle, $token, $amount, $hasBatchId);
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

		/*
		\DB::unprepared(\DB::raw('
			DELETE FROM knishio_access_tokens;
			DELETE FROM knishio_atoms;
			DELETE FROM knishio_bonds;
			DELETE FROM knishio_bundles;
			DELETE FROM knishio_cells;
			DELETE FROM knishio_identifiers;
			DELETE FROM knishio_metas;
			DELETE FROM knishio_molecules;
			DELETE FROM knishio_tokens;
			DELETE FROM knishio_wallets;
			DELETE FROM knishio_wallet_bundles;
		'));
		*/

		// --- Create a non-stackable token
		$tokenMeta = [
			'name'			=> $this->token_slug['fungible'],
			'fungibility'	=> 'fungible',
			'splittable'	=> 0,
			'supply'		=> 'limited',
			'decimals'		=> 0,
			'icon'			=> 'icon',
		];
		$response = $this->client($secret['fungible'])->createToken($this->token_slug['fungible'], $full_amount, $tokenMeta);
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
		$response = $this->client($secret['stackable'])->createToken($this->token_slug['stackable'], $full_amount, $tokenMeta);
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
		$response = $this->client($secret['env'])->createToken($this->token_slug['env.fungible'], $full_amount, $tokenMeta);
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
		$response = $this->client($secret['env'])->createToken($this->token_slug['env.stackable'], $full_amount, $tokenMeta);
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
	public function testRequestToken () {

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
		$client = $this->client($secret['env']);
		$response = $client->requestTokens($token, $transaction_amount, $toBundle[0]);
		$this->checkResponse($response);
		$this->checkWalletShadow($client, $toBundle[0], $token, $transaction_amount * 1.0, false);

		// --- Base receive (NOT-splitting)
		$response = $client->requestTokens($token, $transaction_amount, $toBundle[0]);
		$this->checkResponse($response);
		$this->checkWalletShadow($client, $toBundle[0], $token, $transaction_amount * 2.0, false);

		// --- RECEIVER.1

		// --- Base receive (NOT-splitting)
		$response = $client->requestTokens($token, $transaction_amount, $toBundle[1]);
		$this->checkResponse($response);
		$this->checkWalletShadow($client, $toBundle[1], $token, $transaction_amount * 1.0, false);


		// --- RECEIVER.2

		// --- Base receive (NOT-splitting)
		$response = $client->requestTokens($token, $transaction_amount, $receivers[2]);
		$this->checkResponse($response);
		$this->checkWallet($client, $toBundle[2], $token, $transaction_amount * 1.0, false);

		// --- RECEIVER.3

		// --- Base receive (NOT-splitting)
		$wallet = Wallet::create($receivers[3], $token);
		$response = $client->requestTokens($token, $transaction_amount, $wallet);
		$this->checkResponse($response);
		$this->checkWallet($client, Crypto::generateBundleHash($receivers[3]), $token, $transaction_amount * 1.0, false);

		// Claim shadow wallet
		$this->claimShadowWallet ( $token, $receivers[0], [$receivers[1]] );



		// ------------ STACKABLE ----------------

		// Set token as STACKABLE
		$token = $this->token_slug['env.stackable'];

		// --- Batch receive (splitting)
		$response = $client->requestTokens($token, $transaction_amount, $toBundle[0]);
		$this->checkResponse($response);
		$this->checkWalletShadow($client, $toBundle[0], $token, $transaction_amount * 1.0, true);

		// --- Batch receive (splitting)
		$response = $client->requestTokens($token, $transaction_amount, $toBundle[0]);
		$this->checkResponse($response);
		$this->checkWalletShadow($client, $toBundle[0], $token, $transaction_amount * 1.0, true);

		// --- Batch receive (splitting) WITHOUT a remainder
		$remainder_amount = ($full_amount - $transaction_amount * 2.0);
		$response = $client->requestTokens($token, $remainder_amount, $toBundle[0]);
		$this->checkResponse($response);
		$this->checkWalletShadow($client, $toBundle[0], $token, $remainder_amount, true);
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


		$client = $this->client($from_secret);

		// --- Batch transfer (splitting)
		$response = $client->transferToken($toSecret[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($client, Crypto::generateBundleHash($toSecret[0]), $token, $transaction_amount);

		// --- Batch transfer second transaction (the amount of shadow wallet will be incrementing with a new one)
		$response = $client->transferToken($toSecret[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($client, Crypto::generateBundleHash($toSecret[0]), $token, $transaction_amount * 2.0);



		// --- Batch transfer to other recipient
		$response = $client->transferToken($toSecret[1], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($client, Crypto::generateBundleHash($toSecret[1]), $token, $transaction_amount);



		// --- Batch 1-st transfer
		$response = $client->transferToken($toSecret[2], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($client, Crypto::generateBundleHash($toSecret[2]), $token, $transaction_amount);

		// --- Batch last transfer
		$remainder_amount = $full_amount - $transaction_amount*4.0;
		$response = $client->transferToken($toSecret[2], $token, $remainder_amount);
		$this->checkResponse($response);
		$this->checkWallet($client, Crypto::generateBundleHash($toSecret[2]), $token, $remainder_amount + $transaction_amount);

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

		$client = $this->client($from_secret);


		// --- Batch transfer (splitting)
		$response = $client->transferToken($toBundle[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($client, $toBundle[0], $token, $transaction_amount, true);

		// --- Batch transfer second transaction (the amount of shadow wallet will be incrementing with a new one)
		$response = $client->transferToken($toBundle[0], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($client, $toBundle[0], $token, $transaction_amount, true);



		// --- Batch transfer to other recipient
		$response = $client->transferToken($toBundle[1], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($client, $toBundle[1], $token, $transaction_amount, true);



		// --- Batch 1-st transfer
		$response = $client->transferToken($toBundle[2], $token, $transaction_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($client, $toBundle[2], $token, $transaction_amount, true);

		// --- Batch last transfer
		$remainder_amount = $full_amount - $transaction_amount * 4.0;
		$response = $client->transferToken($toBundle[2], $token, $remainder_amount);
		$this->checkResponse($response);
		$this->checkWalletShadow($client, $toBundle[2], $token, $remainder_amount, true);
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
		$this->claimShadowWallet ( $token, $recipients[0], [$recipients[1]] );
	}


	/**
	 * Test V isotope combnation (multi-recipients)
	 *
	 * @throws \ReflectionException
	 */
	public function testVIsotopeCombination () {
		$this->beforeExecute();

		// Data
		$data = $this->getData();
		$token = $this->token_slug['fungible'];
		$transaction_amount = array_get($data, 'amount.transaction');
		$full_amount = array_get($data, 'amount.full');
		$custom_transaction_amount = 1;

		// Recipient.2: last transaction from wallet => recipient.0 without remainder
		$from_secret = array_get($data, 'secret.recipient')[2];

		// Client for the secret
		$client = $this->client($from_secret);

		// Recipients
		$recipients = [
			array_get($data, 'secret.fungible'),
			array_get($data, 'secret.recipient.0'),
			array_get($data, 'secret.recipient.1')
		];

		// With accumulation recipients
		$response = $this->vIsotopeCombination ($from_secret, $token, $recipients, false, $custom_transaction_amount);
		$this->checkResponse($response);
		$this->checkWallet($client, Crypto::generateBundleHash($recipients[0]), $token, 1);
		$this->checkWallet($client, Crypto::generateBundleHash($recipients[1]), $token, $transaction_amount * 2 + $custom_transaction_amount);
		$this->checkWallet($client, Crypto::generateBundleHash($recipients[2]), $token, $transaction_amount * 1 + $custom_transaction_amount);

		// With new wallets
		$response = $this->vIsotopeCombination ($from_secret, $token, $recipients, true, $custom_transaction_amount);

		$wallet_match_error = strpos($response->reason(), 'Wallet does not match to existing one');
		$this->assertNotEquals($wallet_match_error, false);
		$this->assertEquals($response->status(), 'rejected');
	}


	/**
	 * @param $from_secret
	 * @param $token
	 * @param $recipients
	 * @return mixed
	 * @throws \ReflectionException
	 */
	protected function vIsotopeCombination ($from_secret, $token, $recipients, $generate_wallets = false, $transaction_amount = 1) {

		// Client for the secret
		$client = $this->client($from_secret);

		// Wallets
		$source_wallet = $client->queryBalance( $token, Crypto::generateBundleHash($from_secret) )->payload();

		$recipient_wallets = [];
		foreach ($recipients as $recipient) {

			// Get existing wallets
			if (!$generate_wallets) {

				// Get shadow wallet list
				$query = $client->createQuery(QueryWalletList::class);
				$response = $query->execute([
					'bundleHash' => Crypto::generateBundleHash($recipient),
					'token' => $token,
				]);
				$wallets = $response->payload();

				// Set a recipient wallet
				$recipient_wallets[] = $wallets ? end($wallets) : new Wallet($from_secret, $token);
			}

			// Generate a new wallet
			else {
				$recipient_wallets[] = new Wallet($recipient, $token);
			}
		}
		$remainder_wallet = new Wallet($from_secret, $token);

		// Value
		$value = count($recipient_wallets) * $transaction_amount;

		// Create a meta molecule
		$molecule = $client->createMolecule( $from_secret, $source_wallet, $remainder_wallet );

		// Initializing a new Atom to remove tokens from source
		$molecule->addAtom (new \WishKnish\KnishIO\Client\Atom(
			$source_wallet->position,
			$source_wallet->address,
			'V',
			$source_wallet->token,
			-$value,
			$source_wallet->batchId,
			null,
			null,
			null,
			null,
			$molecule->generateIndex()
		));


		// Add recipient wallets
		foreach ($recipient_wallets as $recipient_wallet) {

			// Initializing a new Atom to add tokens to recipient
			$molecule->addAtom(new \WishKnish\KnishIO\Client\Atom(
				$recipient_wallet->position,
				$recipient_wallet->address,
				'V',
				$source_wallet->token,
				$value / count($recipient_wallets),
				$recipient_wallet->batchId,
				'walletBundle',
				$recipient_wallet->bundle,
				null,
				null,
				$molecule->generateIndex()
			));

		}

		// Initializing a new Atom to deposit remainder in a new wallet
		$molecule->addAtom( new \WishKnish\KnishIO\Client\Atom(
			$remainder_wallet->position,
			$remainder_wallet->address,
			'V',
			$source_wallet->token,
			$source_wallet->balance - $value,
			$remainder_wallet->batchId,
			'walletBundle',
			$source_wallet->bundle,
			null,
			null,
			$molecule->generateIndex()
		));

		// Sign & check the molecule
		$molecule->sign();
		$molecule->check($source_wallet);

		// Create & execute a query
		return $client->createMoleculeMutation(MutationProposeMolecule::class, $molecule)
			->execute();
	}



	/**
	 * Claim shadow wallet
	 *
	 * @param array $recipients: 0-indexed recipient is original recipient (right), > 0-indexed recipient is wrong
	 * @throws \Exception
	 */
	protected function claimShadowWallet ($token, $recipient, array $intruders)
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
		$query = $this->client($recipient)->createQuery(QueryLinkIdentifierMutation::class);
		$response = $query->execute([
			'bundle'	=> $bundleHash,
			'type'		=> 'email',
			'content'	=> $email,
		]);
		if (!$response->success() ) {
			$this->debug ($response, true);
		}


		// Get code from the debug response OR try to get code from the log file
		$code = $response->message(); // ?? $this->getVerificationCode();

		// Get a verification code
		$this->output ([
			'Identifier creating...',
			'Bundle hash: '. $bundleHash,
			'Email: '. $email,
			'Verification code: '. $code
		]);

		// --- Try to create identifier with WRONG code: rejected
		$id_response = $this->client($recipient)->createIdentifier('email', $email, Strings::randomString(8));
		$this->assertEquals($id_response->success(), false);

		// --- Bind a shadow wallet with RIGHT code
		$id_response = $this->client($recipient)->createIdentifier('email', $email, $code);
		$this->checkResponse ($id_response);
		// ---

		// --- Bind a shadow wallet (with wrong bundle hash)
		foreach ($intruders as $intruder) {

			// Client
			$client = $this->client($recipient);

			// Init recipient query
			$response = $client->claimShadowWallet( $token, $client->createMolecule( $intruder, new Wallet( $intruder ) ) );

			// Assert a rejected status
			$this->assertEquals($response->status(), 'rejected');

			/*
			$continue_id_error = strpos($response->reason(), 'ContinuID verification failure');
			if (!$continue_id_error) {
				$this->debug ($response, true);
			}
			$this->assertEquals($continue_id_error, true);
			*/
		}

		// --- Bind a shadow wallet (with original bundle hash)
		$response = $this->client($recipient)->claimShadowWallet($token);
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
