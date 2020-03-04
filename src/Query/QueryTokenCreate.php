<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseTokenCreate;
use WishKnish\KnishIO\Client\Wallet;


/**
 * Class QueryTokenCreate
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryTokenCreate extends QueryMoleculePropose
{


	/**
	 * @param $secret
	 * @param $token
	 * @param $amount
	 * @param array $metas
	 * @throws \ReflectionException
	 */
	public function fillMolecule ($secret, Wallet $sourceWallet, Wallet $recipientWallet, $amount, array $metas = null, Wallet $remainderWallet = null)
	{
		// Default metas value
		$metas = \default_if_null($metas, []);

		// Remainder wallet
		$this->remainderWallet = \default_if_null($remainderWallet, new Wallet ($secret) );



		// Fill the molecule
		$this->molecule->initTokenCreation (
			$sourceWallet, $recipientWallet, $this->remainderWallet, $amount, $metas
		);

		// Sign a molecule
		$this->molecule->sign( $secret );

		// Check the molecule
		$this->molecule->check();
	}

    /**
     * Create a response
     *
     * @param string $response
     * @return Response
     */
    public function createResponse ($response) {
        return new ResponseTokenCreate($this, $response);
    }

}
