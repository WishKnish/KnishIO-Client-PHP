<?php
namespace WishKnish\KnishIO\Client\Response;

use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Wallet;
use Exception;

/**
 * Class ResponseContinueId
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseContinueId extends Response
{
    /**
     * @var string
     */
    protected $dataKey = 'data.ContinueId';

    /**
     * Get a payload
     *
     * @return Wallet|null
     * @throws InvalidResponseException
     * @throws Exception
     */
    public function payload ()
    {
        $wallet = null;
        $continueId = $this->data();

        if ( $continueId !== null ) {
            $wallet             = new Wallet( null, $continueId[ 'tokenSlug' ] );
            $wallet->address    = $continueId[ 'address' ];
            $wallet->position   = $continueId[ 'position' ];
            $wallet->bundle     = $continueId[ 'bundleHash' ];
            $wallet->batchId    = $continueId[ 'batchId' ];
            $wallet->characters = $continueId[ 'characters' ];
            $wallet->pubkey     = $continueId[ 'pubkey' ];
            $wallet->balance    = $continueId[ 'amount' ];
        }

        return $wallet;
    }
}
