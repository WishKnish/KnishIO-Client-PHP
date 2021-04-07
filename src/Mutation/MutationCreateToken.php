<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Mutation;

use ReflectionException;
use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseTokenCreate;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class MutationCreateToken
 * @package WishKnish\KnishIO\Client\Query
 */
class MutationCreateToken extends MutationProposeMolecule
{

  /**
   * @param Wallet $recipientWallet
   * @param $amount
   * @param array|null $meta
   *
   * @return MutationCreateToken
   * @throws ReflectionException
   */
	public function fillMolecule ( Wallet $recipientWallet, $amount, array $meta = null )
	{
		// Default metas value
    $meta = default_if_null( $meta, [] );

		// Fill the molecule
		$this->molecule->initTokenCreation ( $recipientWallet, $amount, $meta );
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
        return new ResponseTokenCreate( $this, $response );
    }

}
