<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Mutation;

use Illuminate\Support\Facades\DB;
use WishKnish\KnishIO\Client\HttpClient\HttpClientInterface;
use WishKnish\KnishIO\Client\MoleculeStructure;
use WishKnish\KnishIO\Client\Query\Query;
use WishKnish\KnishIO\Client\Response\ResponseMolecule;
use WishKnish\KnishIO\Models\Resolvers\Molecule\MoleculeResolver;

/**
 * Class MutationProposeMoleculeStructure
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationProposeMoleculeStructure extends Query
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
	protected $moleculeStructure;


	/**
	 * @param string $json
	 * @return mixed
	 * @todo: tmp function not required to pass it to other clients
	 */
	public static function rawExecute( string $json ): ResponseMolecule
	{
		$molecule = json_decode( $json, true );
		$molecule = \WishKnish\KnishIO\Client\MoleculeStructure::toObject( $molecule );
		$query = new MutationProposeMoleculeStructure(
			( new \WishKnish\KnishIO\Client\KnishIOClient )->client(),
			$molecule
		);
		return $query->execute();
	}


	/**
	 * @param string $json
	 * @return ResponseMolecule
	 * @todo: tmp function not required to pass it to other clients
	 */
	public static function rawExecuteLocal( string $json ): void
	{
		$resolver = static::rawVerify( $json );

		try {

			// Execute molecule function: Get a final molecule model from the resolver (execute call)
			\DB::transaction( static function () use ( $resolver ) {
				$resolver->execute();
			} );

		}
		catch ( \Exception $e ) {

			// Transaction rollback
			\DB::rollBack();

			// Throw to the final try-catch block
			throw $e;
		}

	}

	/**
	 * @param string $json
	 * @return mixed
	 * @todo: tmp function to verify json molecule by MoleculeResolver
	 */
	public static function rawVerify( string $json ): MoleculeResolver
	{
		$molecule = \WishKnish\KnishIO\Client\Response\ResponseMoleculeList::toClientMolecule(
			json_decode($json, true)
		);
		return \WishKnish\KnishIO\Models\Resolvers\Molecule\MoleculeResolver::create( $molecule );
	}

  /**
   * MutationProposeMoleculeStructure constructor.
   *
   * @param HttpClientInterface $client
   * @param MoleculeStructure $moleculeStructure
   * @param string|null $query
   */
	public function __construct ( HttpClientInterface $client, MoleculeStructure $moleculeStructure, string $query = null )
	{
		parent::__construct( $client, $query );

		// Create a molecule
		$this->moleculeStructure = $moleculeStructure;
	}


	/**
	 * @param array|null $variables
	 * @return mixed
	 */
	public function compiledVariables ( array $variables = null ): array
	{
		// Default variabled
		$variables = parent::compiledVariables( $variables );

		// Merge variables with a molecule key
		return array_merge( $variables, [ 'molecule' => $this->moleculeStructure ] );
	}


	/**
	 * @return Molecule
	 */
	public function moleculeStructure(): MoleculeStructure
	{
		return $this->moleculeStructure;
	}


	/**
	 * @param $response
	 * @return ResponseMolecule
	 */
	public function createResponse( $response )
	{
		return new ResponseMolecule( $this, $response );
	}



}