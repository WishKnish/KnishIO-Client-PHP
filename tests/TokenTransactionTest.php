<?php

namespace WishKnish\KnishIO\Client\Tests;

use PHPUnit\Framework\TestCase as StandartTestCase;
use WishKnish\KnishIO\Client\KnishIO;
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Molecule;


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
	protected function saveData (array $data) {
		file_put_contents($this->data_filepath, \json_encode($data));
	}


	/**
	 * @return mixed
	 */
	protected function getData () {
		return json_decode(file_get_contents($this->data_filepath), true);
	}


	/**
	 * @return mixed
	 */
	protected function clearData () {
		if (file_exists($this->data_filepath) ) {
			unlink($this->data_filepath);
		}
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

		// Create a secret key
		$secret = Crypto::generateSecret(null, 2048);

		// Token meta initialization
		$tokenMeta = [
			'name'			=> $this->token_slug['fungible'],
			'fungibility'	=> 'fungible',
			'splittable'	=> 0,
			'supply'		=> 'limited',
			'decimals'		=> 0,
			'icon'			=> 'name',
		];

		// Create a token
		$response = KnishIO::createToken($secret, $this->token_slug['fungible'], 1000, $tokenMeta);

		// Save data
		$this->saveData (['secret' => $secret]);
	}

	/**
	 * Test create token
	 *
	 * @throws \ReflectionException
	 */
	public function testCreateToken2 () {

		// Initial code
		$this->beforeExecute ();

		// Create a secret key
		$secret = Crypto::generateSecret(null, 2048);

		// Token meta initialization
		$tokenMeta = [
			'name'			=> $this->token_slug['stackable'],
			'fungibility'	=> 'fungible',
			'splittable'	=> 0,
			'supply'		=> 'limited',
			'decimals'		=> 0,
			'icon'			=> 'name',
		];

		// Create a token
		$response = KnishIO::createToken($secret, $this->token_slug['stackable'], 1000, $tokenMeta);

		// Save data
		$this->saveData (['secret' => $secret]);
	}


}
