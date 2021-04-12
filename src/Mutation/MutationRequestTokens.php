<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Mutation;

use WishKnish\KnishIO\Client\Molecule;
use WishKnish\KnishIO\Client\Wallet;

/**
 * Class MutationRequestTokens
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationRequestTokens extends MutationProposeMolecule
{

  /**
   * @param string $tokenSlug
   * @param $requestedAmount
   * @param string $metaType
   * @param string $metaId
   * @param array|null $metas
   * @param string|null $batchId
   *
   * @return MutationRequestTokens
   * @throws \ReflectionException
   */
	public function fillMolecule ( string $tokenSlug, $requestedAmount, string $metaType, string $metaId, array $metas = null, ?string $batchId = null )
	{
		// Default metas value
		$metas = default_if_null( $metas, [] );

		// Fill the molecule
		$this->molecule->initTokenRequest( $tokenSlug, $requestedAmount, $metaType, $metaId, $metas, $batchId );
		$this->molecule->sign();
		$this->molecule->check();

		return $this;
	}

}
