<?php
namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseContinuId;


/**
 * Class QueryContinuId
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryContinuId extends Query
{
    // Query
    /**
     * @var string
     */
    protected static $query = 'query ($bundle: String!) { ContinuId(bundle: $bundle) { 
    	@fields 
    } }';

	// Fields
	protected $fields = [
		'address',
		'bundleHash',
		'tokenSlug',
		'position',
		'batchId',
		'characters',
		'pubkey',
		'amount',
		'createdAt',
	];


	/**
     * Create a response
     *
     * @param string $response
     * @return Response
     */
    public function createResponse ( $response )
    {
        return new ResponseContinuId( $this, $response );
    }
}
