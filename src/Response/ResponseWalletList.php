<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\Response;

use WishKnish\KnishIO\Client\Exception\WalletShadowException;
use WishKnish\KnishIO\Client\Wallet;
use WishKnish\KnishIO\Client\WalletShadow;

/**
 * Class ResponseWalletList
 * @package WishKnish\KnishIO\Client\Response
 */
class ResponseWalletList extends Response
{
	protected $dataKey = 'data.Wallet';


	/**
	 * @param array $data
	 * @throws \Exception
	 */
	public static function toClientWallet (array $data) {

		// Shadow wallet
		if ($data[ 'position' ] === null) {
			$wallet = new WalletShadow( $data['bundleHash'], $data['tokenSlug'], $data['batchId'] );
		}

		// Regular wallet
		else {
			$wallet = new Wallet( null, $data[ 'tokenSlug' ] );
			$wallet->address = $data[ 'address' ];
			$wallet->position = $data[ 'position' ];
			$wallet->bundle = $data[ 'bundleHash' ];
			$wallet->batchId = $data[ 'batchId' ];
			$wallet->characters = $data[ 'characters' ];
			$wallet->pubkey = $data[ 'pubkey' ];
		}

		// Bind other data
		$wallet->balance = $data[ 'amount' ];

		return $wallet;
	}


	/**
	 * @return array|null
	 * @throws \Exception
	 */
	public function payload()
	{
		// Get data
		$list = $this->data();
		if (!$list) {
			return null;
		}

		// Get a list of client wallets
		$wallets = [];
		foreach ($list as $item) {
			$wallet = static::toClientWallet($item);
			if (!$wallet instanceof WalletShadow) {
				throw new WalletShadowException();
			}
			$wallets[] = $wallet;
		}

		// Return a wallets list
		return $wallets;
	}




}
