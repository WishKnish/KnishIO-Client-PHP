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
     * @param string $secret
     * @param Wallet $wallet
     * @param Wallet $remainderWallet
     * @throws Exception
     */
    public function fillMolecule ( $secret, Wallet $wallet, Wallet $remainderWallet = null )
    {
        $this->remainderWallet = default_if_null( $remainderWallet, new Wallet( $secret ) );

        // Create a molecule
        $this->molecule = new Molecule();

        $this->molecule->initAuthentication( $wallet, $this->remainderWallet );

        // Check & sign a molecule
        $this->molecule->sign( $secret );
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
