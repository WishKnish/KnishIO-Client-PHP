<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseIdentifier;


/**
 * Class QueryLinkIdentifierMutation
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryLinkIdentifierMutation extends Query
{
	// Query
	protected static $default_query = 'mutation( $bundle: String!, $type: String!, $content: String! ) { LinkIdentifier( bundle: $bundle, type: $type, content: $content ) 
		@fields
	}';

	// Fields
	protected $fields = [
		'type',
		'bundle',
		'content',
		'set',
		'message',
	];



	/**
	 * Create a response
	 *
	 * @param string $response
	 * @return Response
	 */
	public function createResponse ( $response )
    {
		return new ResponseIdentifier( $this, $response );
	}

}
