<?php
namespace WishKnish\KnishIO\Client\Mutation;

use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseAuthorization;
use WishKnish\KnishIO\Client\Wallet;
use Exception;

/**
 * Class MutationRequestAuthorization
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationRequestAuthorization extends MutationProposeMolecule
{


	/**
	 * Fill the molecule
	 */
    public function fillMolecule ()
    {
        $this->molecule->initAuthorization();
		$this->molecule->sign();
		$this->molecule->check();

		return $this;
    }

    /**
     * Create a response
     *
     * @param string $response
     * @return Response
     */
    public function createResponse ( $response )
    {
        return new ResponseAuthorization( $this, $response );
    }
}
