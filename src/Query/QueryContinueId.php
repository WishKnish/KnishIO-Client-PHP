<?php
namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseContinueId;


/**
 * Class QueryBalance
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryContinueId extends Query
{
    // Query
    /**
     * @var string
     */
    protected static $query = 'query ($bundle: String!) { ContinueId(bundle: $bundle) { address, bundleHash, tokenSlug, position, batchId, characters, pubkey, amount, createdAt } }';

    /**
     * Create a response
     *
     * @param string $response
     * @return Response
     */
    public function createResponse ( $response )
    {
        return new ResponseContinueId( $this, $response );
    }
}