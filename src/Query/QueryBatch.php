<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Response\ResponseBatch;

/**
 * Class QueryMetaBatch
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryBatch extends Query
{
  // Query
  protected static $default_query = 'query( $batchId: String ) { Batch( batchId: $batchId )
		@fields
	}';

  // Fields
  protected $fields = [
    'batchId',
    'type',
    'createdAt',
    'wallet' => [
      'address',
      'bundleHash',
      'amount',
    ],
    'metas' => [
      'key',
      'value',
    ],
  ];

  /**
   * @param $response
   *
   * @return ResponseBatch
   */
  public function createResponse ( $response ) {
    return new ResponseBatch( $this, $response );
  }


}
