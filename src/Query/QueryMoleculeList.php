<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;


use WishKnish\KnishIO\Client\Response\ResponseMoleculeList;


/**
 * Class QueryMoleculeList
 * @package WishKnish\KnishIO\Client\Query
 *
 * /graphql?query={Molecule(lastMolecularHash:"",limit:10,order:"created_at asc"){molecularHash}}
 */
class QueryMoleculeList extends Query
{
	// Query
	protected static $default_query = 'query( $status: String, $lastMolecularHash: String, $limit: Int, $order: String ) { Molecule( status: $status, lastMolecularHash: $lastMolecularHash, limit: $limit, order: $order )
	 	@fields
	}';

	// Fields
	protected $fields = [
		'molecularHash',
		'cellSlug',
		'bundleHash',
//		'height',
		'createdAt',
		'atoms' => [
			'position',
			'isotope',
			'walletAddress',
			'tokenSlug',
			'batchId',
			'value',
			'index',
			'metaType',
			'metaId',
			'otsFragment',
			'createdAt',
			'metas' => [
				'key',
				'value',
			],
		],
	];



	/**
	 * @param $response
	 * @return Response|ResponseMoleculeList
	 */
	public function createResponse ( $response ) {
		return new ResponseMoleculeList( $this, $response);
	}

}
