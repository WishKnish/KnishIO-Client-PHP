<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\KnishIO;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Client\WalletShadow;


/**
 * Class QueryTokenTransfer
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryTokenTransfer extends QueryMoleculePropose
{


	/**
	 * @param $secret
	 * @param $token
	 * @param $amount
	 * @param array $metas
	 * @throws \ReflectionException
	 */
	public function initMolecule ($fromSecret, Wallet $fromWallet, Wallet $toWallet, $token, $amount, Wallet $remainderWallet = null)
	{
		// Remainder wallet (set a batch ID from the recipient wallet)
		$this->remainderWallet = \default_if_null ($remainderWallet, new Wallet( $fromSecret, $token ) );
		$this->remainderWallet->batchId = $toWallet->batchId;


		// Create & sign a molecule
		$this->molecule = new Molecule();
		$this->molecule->initValue( $fromWallet, $toWallet, $this->remainderWallet, $amount );
		$this->molecule->sign( $fromSecret );

		// Check the molecule
		$this->molecule->check($fromWallet);
	}


}
