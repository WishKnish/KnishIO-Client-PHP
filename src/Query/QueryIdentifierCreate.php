<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;


/**
 * Class QueryIdentifierCreate
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryIdentifierCreate extends QueryMoleculePropose
{

	/**
	 * @param $type
	 * @param $contact
	 * @param $code
	 * @param Wallet|null $remainderWallet
	 * @throws \Exception
	 */
	public function fillMolecule ( $type, $contact, $code, Wallet $remainderWallet = null)
	{
		// Remainder wallet
		$this->remainderWallet = \default_if_null($remainderWallet, new Wallet ( $this->secret ) );

		// Fill the molecule
		$this->molecule->initIdentifierCreation ($this->sourceWallet, $this->remainderWallet, $type, $contact, $code);
	}


}
