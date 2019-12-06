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
	public function initMolecule (string $fromSecret, Wallet $fromWallet, Wallet $toWallet, string $token, $amount, Wallet $remainderWallet = null)
	{
		// Remainder wallet
		$this->remainderWallet = $remainderWallet ?? new Wallet( $fromSecret, $token );



		// --- BEGIN: Batch ID initialization
		if ($fromWallet->batchId) {

			// Has a remainder & is the first transaction to shadow wallet (toWallet has not a batchID)
			if (!$toWallet->batchId && ($fromWallet->balance - $amount) > 0) {
				$batchId = Wallet::generateBatchId();
			}

			// Has no remainder?: use batch ID from the source wallet
			else {
				$batchId = $fromWallet->batchId;
			}

			// Set batchID to recipient & remainder wallets
			$toWallet->batchId = $batchId;
			$this->remainderWallet->batchId = $batchId;
		}
		// --- END: Batch ID initialization



		// Create & sign a molecule
		$this->molecule = new Molecule();
		$this->molecule->initValue( $fromWallet, $toWallet, $this->remainderWallet, $amount );
		$this->molecule->sign( $fromSecret );

		// Check the molecule
		$this->molecule->check($fromWallet);
	}


}
