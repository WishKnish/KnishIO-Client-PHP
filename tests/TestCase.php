<?php

namespace WishKnish\KnishIO\Client\Tests;

// Supporing variety versions of PHPUnit
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;


if (!class_exists('\PHPUnit_Framework_TestCase') ) {
	abstract class TestCaseBase extends \PHPUnit\Framework\TestCase {}
}
else {
	abstract class TestCaseBase extends \PHPUnit_Framework_TestCase {}
}


/**
 * Class TestCase
 * @package WishKnish\KnishIO\Client\Tests
 */
abstract class TestCase extends TestCaseBase {


	protected $client;
	protected $dotenv;


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



	/**
	 * @param array $response
	 */
	protected function checkResponse (Response $response) {

		// Check molecule response
		if ($response instanceof ResponseMolecule) {
			if (!$response->success()) {
				$this->debug($response, true);
			}
			$this->assertEquals($response->status(), 'accepted');
		}

		// Default response
		else {
			if (!$response->data() ) {
				$this->debug($response, true);
			}
		}
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


	/**
	 * Output
	 *
	 * @param array $info
	 */
	protected function output (array $info) {
		echo implode("\r\n", $info)."\r\n\r\n";
	}


	/**
	 * Clear data test
	 *
	 * @throws \ReflectionException
	 */
	protected function callThirdPartyTest ($class, $test, $test_dir) {

		// PHP version
		$this->output (['PHP Version: '.PHP_VERSION]);

		// PHP version comparing
		if (version_compare(PHP_VERSION, '7.0.0') <= 0) {
			$this->output ([
				"PHP version is less than 7.0.0. Skip '{$test}' test.",
				"  -- DB must be cleaned manually",
				"  -- OR should call {$class}::{$test} server unit test instead.",
			]);
			return;
		}

		// Server test filepath
		$server_test_filepath = $test_dir.class_basename($class).'.php';

		// File does not exist
		if (!$server_test_filepath || !file_exists($server_test_filepath) ) {
			print_r("SERVER_TEST_FILE is not defined. Test do not clean up.\r\n");
		}
		else {

			// Create & run a unit test command
			$command = new \PHPUnit\TextUI\Command();
			$response = $command->run([
				'phpunit',
				'--configuration',
				__DIR__.'/../' .'\phpunit.xml',
				'--filter',
				'/(::'.$test.')( .*)?$/',
				$class,
				$server_test_filepath,
				'--teamcity',
			], false);
		}
		$this->assertEquals(true, true);
	}


	/**
	 * @throws \ReflectionException
	 */
	protected function callServerCleanup ($class) {
		$this->callThirdPartyTest($class, 'testClearAll', getenv('SERVER_TEST_PATH') );
	}

}
