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
class QueryTokenReceive extends QueryMoleculePropose
{


	/**
	 * @param $fromSecret
	 * @param Wallet $recipientWallet
	 * @param $token
	 * @param $value
	 * @param Wallet|null $remainderWallet
	 * @throws \ReflectionException
	 */
	public function initMolecule ($secret, Wallet $sourceWallet, Wallet $recipientWallet, $value, array $metas = null, Wallet $remainderWallet = null)
	{
		// Default metas value
		$metas = \default_if_null($metas, []);

		// Remainder wallet
		$this->remainderWallet = \default_if_null($remainderWallet, new Wallet ($secret) );

		// Create & sign a molecule
		$this->molecule = new Molecule();
		$this->molecule->initTokenReceive( $sourceWallet, $recipientWallet, $this->remainderWallet, $value, $metas );
		$this->molecule->sign( $secret );


		// Check the molecule
		$this->molecule->check();
	}


}
