<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class QueryMoleculePropose
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryMoleculePropose extends QueryMoleculeStructurePropose
{
	// Molecule
	protected $molecule;

	// Remainder wallet
	protected $remainderWallet;


	/**
	 * QueryMoleculePropose constructor.
	 * @param HttpClientInterface $client
	 * @param Molecule $molecule
	 * @param string|null $query
	 */
	public function __construct ( HttpClientInterface $client, Molecule $molecule, string $query = null )
	{
		parent::__construct( $client, $molecule, $query );

		$this->molecule = $molecule;
	}


	/**
	 * @return Molecule
	 */
	public function molecule(): Molecule
	{
		return $this->molecule;
	}


	/**
	 * @return mixed
	 */
	public function remainderWallet(): Wallet
    {
		return $this->remainderWallet;
	}


}
