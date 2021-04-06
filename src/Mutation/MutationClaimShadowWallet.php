<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Mutation;

use WishKnish\KnishIO\Client\Wallet;

/**
 * Class MutationClaimShadowWallet
 * @package WishKnish\KnishIO\Client\Mutation
 */
class MutationClaimShadowWallet extends MutationProposeMolecule
{

  /**
   * @param string $tokenSlug
   * @param string|null $batchId
   *
   * @return MutationClaimShadowWallet
   * @throws \ReflectionException
   */
    public function fillMolecule( string $tokenSlug, ?string $batchId = null )
    {
        // Create a wallet
        $wallet = Wallet::create( $this->molecule->secret(), $tokenSlug, $batchId );

        // Init shadow wallet claim
        $this->molecule->initShadowWalletClaim( $tokenSlug, $wallet );
        $this->molecule->sign();
        $this->molecule->check();

        return $this;
    }


}
