<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Response;

use WishKnish\KnishIO\Client\Exception\InvalidResponseException;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Client\WalletShadow;

/**
 * Class ResponseBalance
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseBalance extends Response
{
	protected $dataKey = 'data.Balance';


	/**
	 * Get a payload
	 *
	 * @return Wallet|WalletShadow|null
	 * @throws \Exception
	 */
	public function payload()
	{
		// Get data
		$balance = $this->data();
		if (!$balance) {
			return null;
		}

		// Shadow wallet
		if ($balance[ 'position' ] === null) {
			$wallet = new WalletShadow( $balance['bundleHash'], $balance['tokenSlug'], $balance['batchId'] );
		}

		// Regular wallet
		else {
			$wallet = new Wallet( null, $balance[ 'tokenSlug' ] );
			$wallet->address = $balance[ 'address' ];
			$wallet->position = $balance[ 'position' ];
			$wallet->bundle = $balance[ 'bundleHash' ];
			$wallet->batchId = $balance[ 'batchId' ];
		}

		// Bind other data
		$wallet->balance = $balance[ 'amount' ];

		// Return a wallet
		return $wallet;
	}

}
