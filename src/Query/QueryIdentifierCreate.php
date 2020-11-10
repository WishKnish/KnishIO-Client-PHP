<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;



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
	 * @throws \Exception
	 */
	public function fillMolecule ( $type, $contact, $code )
	{
		$this->molecule->initIdentifierCreation ( $type, $contact, $code );
		$this->molecule->sign();
		$this->molecule->check();

		return $this;
	}


}
