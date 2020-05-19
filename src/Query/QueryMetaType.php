<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;



use WishKnish\KnishIO\Client\Response\ResponseMetaType;

/**
 * Class QueryMetaType
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryMetaType extends Query
{
	// Query
	protected static $query = 'query( $metaType: String, $metaTypes: [ String! ], $metaId: String, $metaIds: [ String! ], $key: String, $keys: [ String! ], $value: String, $values: [ String! ], $count: String, $newLogic: Boolean ) { MetaType( metaType: $metaType, metaTypes: $metaTypes, metaId: $metaId, metaIds: $metaIds, key: $key, keys: $keys, value: $value, values: $values, count: $count, newLogic: $newLogic )
		@fields
	}';

	// Fields
	protected $fields = [
		'metaType',
		'instances' => [
			'metaType',
			'metaId',
			'createdAt',
			'metas' => [
				'molecularHash',
				'position',
				'metaType',
				'metaId',
				'key',
				'value',
				'createdAt',
			],
			'atoms' => [
				'molecularHash',
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
			],
			'molecules' => [
				'molecularHash',
				'cellSlug',
				'bundleHash',
				'status',
				'height',
				'createdAt',
				'receivedAt',
				'processedAt',
				'broadcastedAt',
			],
		],
    	'metas' => [
			'molecularHash',
			'position',
			'metaType',
			'metaId',
			'key',
			'value',
			'createdAt',
    	],
		'createdAt',
	];

	/**
	 * @param string $response
	 * @return \WishKnish\KnishIO\Client\Response\Response|ResponseWalletList
	 */
	public function createResponse ($response) {
		return new ResponseMetaType($this, $response);
	}


}
