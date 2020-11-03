<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;


/**
 * Class QueryTokenTransfer
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryTokenReceive extends QueryMoleculePropose
{

    /**
     * @param string $token
     * @param $value
     * @param string $metaType
     * @param string $metaId
     * @param array|null $metas
     * @throws \ReflectionException|\Exception
     */
	public function fillMolecule ( $token, $value, $metaType, $metaId, array $metas = null )
	{
		// Default metas value
		$metas = default_if_null( $metas, [] );

		// Fill the molecule
		$this->molecule->initTokenTransfer( $token, $value, $metaType, $metaId, $metas );
		$this->molecule->sign();
		$this->molecule->check();

		return $this;
	}

}
