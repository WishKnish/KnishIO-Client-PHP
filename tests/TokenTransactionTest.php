<?php

namespace WishKnish\KnishIO\Tests;

use PHPUnit\Framework\TestCase as StandartTestCase;
use WishKnish\KnishIO\Client\KnishIO;
use WishKnish\KnishIO\Client\Libraries\Crypto;


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
	 * Test create token
	 *
	 * @throws \ReflectionException
	 */
	public function testCreateToken () {

		// Create a secret key
		$secret = Crypto::generateSecret(null, 2048);

		// Create a token
		$response = KnishIO::createToken($secret, $this->token_slug['fungible'], 1000);

		dd ($response);
	}


}
