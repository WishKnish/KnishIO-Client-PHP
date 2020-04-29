<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;


/**
 * Class QueryShadowWalletClaim
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryShadowWalletClaim extends QueryMoleculePropose
{



    /**
     * @param $secret
     * @param $token
     * @param $amount
     * @param array $metas
     * @throws \ReflectionException|\Exception
     */
	public function fillMolecule ($secret, Wallet $sourceWallet, $token, array $shadowWallets)
	{
		// Get new client wallets
		$wallets = [];
		foreach ($shadowWallets as $shadowWallet) {
			$recipientWallet = Wallet::create( $secret, $token, $shadowWallet->batchId );
		}

		// Init shadow wallet claim
		$this->molecule->initShadowWalletClaimAtom ($sourceWallet, $token, $wallets);

		// User remainder atom
		$this->remainderWallet = new Wallet($secret);
		$this->molecule->addUserRemainderAtom ($this->remainderWallet);

		// Sing a molecule
		$this->molecule->sign( $secret );


		// Check the molecule
		$this->molecule->check();
	}


}
