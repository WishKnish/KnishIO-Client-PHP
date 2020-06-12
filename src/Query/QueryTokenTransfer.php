<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseTokenTransfer;
use WishKnish\KnishIO\Client\Wallet;


/**
 * Class QueryTokenTransfer
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryTokenTransfer extends QueryMoleculePropose
{

	/**
	 * @param Wallet $fromWallet
	 * @param Wallet $toWallet
	 * @param $token
	 * @param $amount
	 * @param Wallet|null $remainderWallet
	 * @throws \Exception
	 */
	public function fillMolecule ( Wallet $toWallet, $amount )
	{
		$this->molecule->initValue( $toWallet, $amount );
		$this->molecule->sign();
		$this->molecule->check( $this->molecule->sourceWallet() );
	}


}
