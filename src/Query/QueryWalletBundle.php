<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;


use WishKnish\KnishIO\Client\Response\ResponseWalletBundle;

/**
 * Class QueryWalletBundle
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryWalletBundle extends Query
{

	// Query
	protected static $default_query = 'query( $bundleHash: String, $bundleHashes: [ String! ], $key: String, $keys: [ String! ], $value: String, $values: [ String! ], $keys_values: [ MetaInput ], $latest: Boolean, $limit: Int, $skip: Int, $order: String ) { WalletBundle( bundleHash: $bundleHash, bundleHashes: $bundleHashes, key: $key, keys: $keys, value: $value, values: $values, keys_values: $keys_values, latest: $latest, limit: $limit, skip: $skip, order: $order )
	 	@fields
	}';



	// Fields
	protected $fields = [
		'bundleHash',
		'slug',
		'metas' => [
			'molecularHash',
			'position',
			'metaType',
			'metaId',
			'key',
			'value',
			'createdAt',
		],
	//	'molecules',
	//	'wallets',
		'createdAt',
	];



  /**
   * Builds a GraphQL-friendly variables object based on input fields
   *
   * @param {string|array|null} metaType
   * @param {string|array|null} metaId
   * @param {string|array|null} key
   * @param {string|array|null} value
   * @param {boolean} latest
   * @returns {{}}
   */
  public static function createVariables ( string $bundleHash = null, string $key = null, string $value = null, bool $latest = true ) {

    $variables = [
      'latest' => $latest,
    ];

    if ( $bundleHash ) {
      $variables[ is_string( $bundleHash ) ? 'bundleHash' : 'bundleHashes' ] = $bundleHash;
    }

    if ( key ) {
      $variables[ is_string( $key ) ? 'key' : 'keys' ] = $key;
    }

    if ( value ) {
      $variables[ is_string( $value ) ? 'value' : 'values' ] = $value;
    }

    return $variables;

}


	/**
	 * @param string $response
	 * @return \WishKnish\KnishIO\Client\Response\Response|ResponseWalletBundle
	 */
	public function createResponse ($response) {
		return new ResponseWalletBundle($this, $response);
	}



}
