<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;
use WishKnish\KnishIO\Client\KnishIOClient;
use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;

/**
 * Class QueryMoleculePropose
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryMoleculePropose extends Query
{
	// Query
	protected static $default_query = 'mutation( $molecule: MoleculeInput! ) { ProposeMolecule( molecule: $molecule )
		@fields 
	}';

	// Fields
	protected $fields = [
		'molecularHash',
		'height',
		'depth',
		'status',
		'reason',
		'payload',
		'createdAt',
		'receivedAt',
		'processedAt',
		'broadcastedAt',
	];

	// Molecule
	protected $molecule;

	// Remainder wallet
	protected $remainderWallet;



	/**
	 * Query constructor.
	 * @param KnishIOClient $knishIO
	 * @param $molecule
	 */
	public function __construct ( HttpClientInterface $client, Molecule $molecule, string $query = null )
	{
		parent::__construct( $client, $query );

		// Create a molecule
		$this->molecule = $molecule;
	}


	/**
	 * @param array|null $variables
	 * @return mixed
	 */
	public function compiledVariables ( array $variables = null )
	{
		// Default variabled
		$variables = parent::compiledVariables( $variables );

		// Merge variables with a molecule key
		return array_merge( $variables, [ 'molecule' => $this->molecule ] );
	}


	/**
	 * @return Molecule
	 */
	public function molecule ()
	{
		return $this->molecule;
	}


	/**
	 * Create a response
	 *
	 * @param string $response
	 * @return Response
	 */
	public function createResponse ( $response )
    {
		return new ResponseMolecule( $this, $response );
	}


	/**
	 * @return mixed
	 */
	public function remainderWallet ()
    {
		return $this->remainderWallet;
	}


}
