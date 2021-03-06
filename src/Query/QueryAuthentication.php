<?php
namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseAuthentication;
use WishKnish\KnishIO\Client\Wallet;
use Exception;


/**
 * Class QueryAuthentication
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryAuthentication extends QueryMoleculePropose
{


	/**
	 * Fill the molecule
	 */
    public function fillMolecule ()
    {
        $this->molecule->initAuthentication();
		$this->molecule->sign();
		$this->molecule->check();
    }

    /**
     * Create a response
     *
     * @param string $response
     * @return Response
     */
    public function createResponse ( $response )
    {
        return new ResponseAuthentication( $this, $response );
    }
}
