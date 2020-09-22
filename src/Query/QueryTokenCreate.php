<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Query;

use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseTokenCreate;
use WishKnish\KnishIO\Client\Wallet;


/**
 * Class QueryTokenCreate
 * @package WishKnish\KnishIO\Client\Query
 */
class QueryTokenCreate extends QueryMoleculePropose
{

    /**
     * @param $recipientWallet
     * @param $amount
     * @param array $metas
     * @throws \ReflectionException
     * @throws \Exception
     */
	public function fillMolecule ( Wallet $recipientWallet, $amount, array $metas = null )
	{
		// Default metas value
		$metas = default_if_null( $metas, [] );

		// Fill the molecule
		$this->molecule->initTokenCreation ( $recipientWallet, $amount, $metas );
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
        return new ResponseTokenCreate( $this, $response );
    }

}
