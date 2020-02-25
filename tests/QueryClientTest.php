<?php

namespace WishKnish\KnishIO\Client\Tests;




// !!! @todo: this unit test must to be separated from any server side (it should work as an independent part) !!!
use WishKnish\KnishIO\Client\Libraries\Crypto;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;


/**
 * Class QueryClientTest
 * @package WishKnish\KnishIO\Client\Tests
 */
class QueryClientTest extends TestCase
{
	protected $source_secret;
	protected $source_wallet;


	/**
	 * @throws \Exception
	 */
	public function beforeExecute()
	{
		parent::beforeExecute();

		// Source secret & wallet
		$this->source_secret = Crypto::generateSecret();
		$this->source_wallet = new Wallet ($this->source_secret);
	}


	/**
	 *
	 */
	public function testMetaIsotope () {

		$this->beforeExecute();

		// Create a meta molecule
		$molecule = new Molecule();
		$molecule->initMetaAppend($this->source_wallet, ['key1' => 'value1', 'key2' => 'value2'], 'metaType', 'metaId');
		$molecule->sign($this->source_secret);
		$molecule->check();

		$query = new \WishKnish\KnishIO\Client\Query\QueryMoleculePropose($this->client);
		$response = $query->execute(['molecule' => $molecule]);
		$this->checkResponse ($response);
	}


	public function testAppendMetaIsotope () {
		$this->beforeExecute();
	}


	public function testWalletCreation () {

		$this->beforeExecute();


		$newWallet = new Wallet(Crypto::generateSecret(), 'INITWALLETTEST');

		$molecule = new Molecule();
		$molecule->initWalletCreation($this->source_wallet, $newWallet, new Wallet($this->source_secret));
		$molecule->sign($this->source_secret);

	}

}
