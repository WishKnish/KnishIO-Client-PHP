<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use WishKnish\KnishIO\Client\Libraries\Base58;

/**
 * Class WalletDataController
 * @package WishKnish\KnishIO\Client
 */
class WalletDataController extends Wallet
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
	public function __construct ( $secretOrBundle, $token, string $batchId = null, $characters = null )
	{
		parent::__construct($secretOrBundle, 'USER', null, $characters);



	}

}

