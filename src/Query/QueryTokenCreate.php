<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;


/**
 * Class QueryTokenCreate
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryTokenCreate extends QueryMoleculePropose
{
	protected $fromWallet;
	protected $recipientWallet;
	protected $remainderWallet;



	/**
	 * @param $secret
	 * @param $token
	 * @param $amount
	 * @param array $metas
	 * @throws \ReflectionException
	 */
	public function initMolecule($secret, $token, $amount, array $metas = [])
	{
		// --- Create wallets

		// From wallet
		$this->fromWallet = new Wallet( $secret );

		// Recipient wallet
		$this->recipientWallet = new Wallet( $secret, $token );
		if (array_get($metas, 'fungibility') === 'stackable') { // For stackable token - create a batch ID
			$this->recipientWallet->batchId = Wallet::generateBatchId();
		}

		// Remainder wallet
		$this->remainderWallet = new Wallet ( $secret );



		// --- Create a molecule

		// Create a molecule
		$this->molecule = new Molecule();
		$this->molecule->initTokenCreation (
			$this->fromWallet, $this->recipientWallet, $this->remainderWallet, $amount, $metas
		);

		// Sign a molecule
		$this->molecule->sign( $secret );

		// Check the molecule
		$this->molecule->check();
	}


	/**
	 * @return mixed
	 */
	public function remainderWallet () {
		return $this->remainderWallet;
	}

}
