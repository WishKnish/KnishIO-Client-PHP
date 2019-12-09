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
 * Class QueryWalletClaim
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryWalletClaim extends QueryMoleculePropose
{


	/**
	 * @param $secret
	 * @param $token
	 * @param $amount
	 * @param array $metas
	 * @throws \ReflectionException
	 */
	public function initMolecule (string $secret, Wallet $fromWallet, Wallet $shadowWallet, string $token, Wallet $recipientWallet = null)
	{
		// Create a recipient wallet to generate new position & address
		$this->recipientWallet = $recipientWallet ?? new Wallet( $secret, $token );



		// Meta with address & position to the shadow wallet
		$metas = [
			'walletAddress' 	=> $this->recipientWallet->address,
			'walletPosition'	=> $this->recipientWallet->position,
		];

		// Wallet for user remainder atom
		$this->remainderWallet = new Wallet ( $secret );

		// Create & sign a molecule
		$this->molecule = new Molecule();
		$this->molecule->initShadowWalletClaim( $fromWallet, $shadowWallet, $this->remainderWallet, $metas );
		$this->molecule->sign( $secret );


		// Check the molecule
		$this->molecule->check();
	}


}
