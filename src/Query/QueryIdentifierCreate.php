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
     * @param string $secret
     * @param Wallet $sourceWallet
     * @param string $type
     * @param string $contact
     * @param string $code
     * @param Wallet|null $remainderWallet
	 */
	public function fillMolecule ($secret, Wallet $sourceWallet, $type, $contact, $code, Wallet $remainderWallet = null)
	{
		// Remainder wallet
		$this->remainderWallet = \default_if_null($remainderWallet, new Wallet ( $secret ) );

		// Fill the molecule
		$this->molecule->initIdentifierCreation ($sourceWallet, $this->remainderWallet, $type, $contact, $code);
	}


}
