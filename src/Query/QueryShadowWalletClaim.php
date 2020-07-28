<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Wallet;


/**
 * Class QueryShadowWalletClaim
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryShadowWalletClaim extends QueryMoleculePropose
{


	/**
	 * @param $token
	 * @param array $shadowWallets
	 * @throws \Exception
	 */
	public function fillMolecule ( $token, array $shadowWallets )
	{
		// Get new client wallets
		$wallets = [];
		foreach ($shadowWallets as $shadowWallet) {
			$wallets[] = Wallet::create( $this->molecule->secret(), $token, $shadowWallet->batchId );
		}

		// Init shadow wallet claim
		$this->molecule->initShadowWalletClaimAtom ( $token, $wallets );
		$this->molecule->sign();
		$this->molecule->check();
	}


}
