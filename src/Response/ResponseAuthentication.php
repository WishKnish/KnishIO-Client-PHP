<?php
namespace WishKnish\KnishIO\Client\Response;


/**
 * Class ResponseAuthentication
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseAuthentication extends ResponseMolecule
{

    public function payload ()
    {
        $molecule = $this->data();

        return $molecule[ 'status' ] === 'rejected' ? [] : json_decode( $molecule[ 'reason' ] );
    }
}