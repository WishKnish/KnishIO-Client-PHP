<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use GuzzleHttp\Client;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;

/**
 * Class QueryMoleculePropose
 * @package WishKnish\KnishIO\Client\Query
 */
abstract class QueryMoleculePropose extends Query
{
	// Query
	protected static $query = 'mutation( $molecule: MoleculeInput! ) { ProposeMolecule( molecule: $molecule, ) { molecularHash, height, depth, status, reason, reasonPayload, createdAt, receivedAt, processedAt, broadcastedAt } }';

	// Molecule
	protected $molecule;

	// Remainder wallet
	protected $remainderWallet;


	/**
	 * Create a response
	 *
	 * @param string $response
	 * @return Response
	 */
	public function createResponse ($response) {
		return new ResponseMolecule($this, $response);
	}


	/**
	 * @param array $variables
	 * @return Response
	 */
	public function execute (array $variables = null) {
		return parent::execute (
			array_merge(\default_if_null($variables, []), ['molecule' => $this->molecule])
		);
	}


	/**
	 * @return mixed
	 */
	public function remainderWallet () {
		return $this->remainderWallet;
	}

	/**
	 * @return mixed
	 */
	public function molecule () : Molecule {
		return $this->molecule;
	}

}
