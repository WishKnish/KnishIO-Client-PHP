<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use Exception;
use ReflectionException;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;


/**
 * Class QueryWalletClaim
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryWalletClaim extends QueryMoleculePropose
{

    protected $recipientWallet;

    /**
     * @param string $secret
     * @param Wallet $sourceWallet
     * @param Wallet $shadowWallet
     * @param string $token
     * @param Wallet|null $recipientWallet
     * @throws ReflectionException
     * @throws Exception
     */
	public function initMolecule ( $secret, Wallet $sourceWallet, Wallet $shadowWallet, $token, Wallet $recipientWallet = null )
	{
		// Create a recipient wallet to generate new position & address
		$this->recipientWallet = default_if_null( $recipientWallet, new Wallet( $secret, $token ) );



		// Meta with address & position to the shadow wallet
		$metas = [
			'walletAddress' 	=> $this->recipientWallet->address,
			'walletPosition'	=> $this->recipientWallet->position,
		];

		// Wallet for user remainder atom
		$this->remainderWallet = new Wallet ( $secret );

		// Create & sign a molecule
		$this->molecule = new Molecule();
		$this->molecule->initShadowWalletClaim( $sourceWallet, $shadowWallet, $this->remainderWallet, $metas );
		$this->molecule->sign( $secret );


		// Check the molecule
		$this->molecule->check();
	}


}
