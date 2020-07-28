<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;


use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseWalletList;


/**
 * Class QueryBalance
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryWalletList extends Query
{
	// Query
	protected static $query = 'query( $address: String, $bundleHash: String, $token: String, $position: String ) { Wallet( address: $address, bundleHash: $bundleHash, token: $token, position: $position )
	 	@fields
	}';

	// Fields
	protected $fields = [
		'address',
		'bundleHash',
		'tokenSlug',
		'batchId',
		'position',
		'amount',
		'characters',
		'pubkey',
		'createdAt',
	];


	/**
	 * @param string $response
	 * @return Response|ResponseWalletList
	 */
	public function createResponse ($response) {
		return new ResponseWalletList($this, $response);
	}

}
