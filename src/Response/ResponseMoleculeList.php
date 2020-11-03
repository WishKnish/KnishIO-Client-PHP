<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Response;

use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\MoleculeStructure;


/**
 * Class ResponseMoleculeList
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseMoleculeList extends Response
{
	protected $dataKey = 'data.Molecule';


	/**
	 * @param array $data
	 * @return Wallet|WalletShadow
	 * @throws \Exception
	 */
	public static function toClientMolecule ( array $data ): MoleculeStructure {

		$molecule = new MoleculeStructure();
		$molecule->molecularHash = array_get( $data, 'molecularHash' );

		return $molecule;
	}


	/**
	 * @return array|null
	 * @throws \Exception
	 */
	public function payload()
	{
		// Get data
		$list = $this->data();
		if (!$list) {
			return null;
		}

		// Get a list of client molecules
		$molecules = [];
		foreach ( $list as $item ) {
			$molecules[] = static::toClientMolecule( $item );
		}

		// Return a molecules list
		return $molecules;
	}

}
