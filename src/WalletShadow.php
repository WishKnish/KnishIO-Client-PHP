<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;


use WishKnish\KnishIO\Client\Libraries\Strings;

/**
 * Class WalletShadow
 * @package WishKnish\KnishIO\Client
 */
class WalletShadow extends Wallet
{

	/**
	 * WalletShadow constructor.
	 *
	 * @param string $secret
	 * @param string $token
	 * @param string|null $position
	 * @param integer $saltLength
	 * @throws \Exception
	 */
	public function __construct ( $bundleHash, $token = 'USER', $batchId = null )
	{
		parent::__construct(null, $token);

		// Initialize properties
		$this->bundle = $bundleHash;
		$this->batchId = $batchId;
		$this->key = null;
		$this->address = null;
		$this->privkey = null;
		$this->pubkey = null;
	}


	/**
	 * Generate position (for the Shadow Wallet it always will be NULL)
	 *
	 * @param null $position
	 */
	public function generatePosition ($saltLength = 64) {
		return null;
	}

}

