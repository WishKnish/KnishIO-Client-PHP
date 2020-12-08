<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client;

use WishKnish\KnishIO\Client\Libraries\Base58;

/**
 * Class WalletShadow
 * @package WishKnish\KnishIO\Client
 */
class WalletShadow
{


    /**
     * WalletShadow constructor.
     *
     * @param string $bundleHash
     * @param string $token
     * @param string|null $batchId
     * @param string|null $characters
     * @throws Exception
     */
	public function __construct ( $bundleHash, $token = 'USER', $batchId = null, $characters = null )
	{
	    
        $this->token = $token;
		$this->bundle = $bundleHash;
		$this->batchId = $batchId;
        $this->characters = $characters; //defined(Base58::class . '::' . $characters ) ? $characters : null;

		// Empty values
		$this->position = null;
		$this->key = null;
		$this->address = null;
		$this->pubkey = null;

	}

}

