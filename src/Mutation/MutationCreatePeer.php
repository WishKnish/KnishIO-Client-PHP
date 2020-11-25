<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Mutation;

use WishKnish\KnishIO\Client\Response\Response;
use WishKnish\KnishIO\Client\Response\ResponseTokenCreate;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class MutationCreatePeer
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationCreatePeer extends MutationProposeMolecule
{

	/**
	 * @param $recipientWallet
	 * @param $amount
	 * @param array $metas
	 * @throws \ReflectionException
	 * @throws \Exception
	 */
	public function fillMolecule ( string $slug, string $host, string $name = null, array $cellSlugs = [] )
	{
		// Set name as slug if it does not defined
		$name = $name ?: $slug;

		// Fill the molecule
		$this->molecule->initPeerCreation ( $slug, $host, $name, $cellSlugs );
		$this->molecule->sign();
		$this->molecule->check();

		return $this;
	}


}
