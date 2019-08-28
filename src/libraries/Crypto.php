<?php
// Copyright 2019 WishKnish Corp. All rights reserved.
// You may use, distribute, and modify this code under the GPLV3 license, which is provided at:
// https://github.com/WishKnish/KnishIO-Client-JS/blob/master/LICENSE
// This experimental code is part of the Knish.IO API Client and is provided AS IS with no warranty whatsoever.

namespace WishKnish\KnishIO\Client\libraries;

use desktopd\SHA3\Sponge as SHA3;

/**
 * Class Crypto
 * @package WishKnish\KnishIO\Client\libraries
 */
class Crypto
{

	/**
	 * Generates a secret based on an optional seed
	 *
	 * @param null $seed
	 * @param int $length
	 * @return string
	 */
	public static function generateSecret ( $seed = null, $length = 2048 )
	{
		if ( $seed ) {
			return bin2hex( SHA3::init( SHA3::SHAKE256 )
				->absorb( $seed )
				->squeeze( $length / 4 ) );
		} else {
			return Strings::randomString( $length );
		}
	}

	/**
	 * Hashes the user secret to produce a bundle hash
	 *
	 * @param string $secret
	 * @return string
	 * @throws \Exception
	 */
	public static function generateBundleHash ( $secret )
	{
		return bin2hex( SHA3::init( SHA3::SHAKE256 )
			->absorb( $secret )
			->squeeze( 32 ) );
	}
}
