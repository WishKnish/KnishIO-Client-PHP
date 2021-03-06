<?php
namespace WishKnish\KnishIO\Client\Response;

use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Wallet;
use Exception;

/**
 * Class ResponseContinuId
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseContinuId extends Response
{
    /**
     * @var string
     */
    protected $dataKey = 'data.ContinuId';


    /**
     * Get a payload
     *
     * @return Wallet|null
     * @throws InvalidResponseException
     * @throws Exception
     */
    public function payload ()
    {
    	// Check response
		$data = $this->data();
        if ( $data !== null ) {
			return ResponseWalletList::toClientWallet($data);
        }

        return null;
    }
}
