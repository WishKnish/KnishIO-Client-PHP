<?php

namespace WishKnish\KnishIO\Client\Libraries\Crypto;

use desktopd\SHA3\Sponge as SHA3;


/**
 * Class Shake256
 * @package WishKnish\KnishIO\Client\Libraries\Crypto
 */
class Shake256 {

	protected static $useExt = true;


	/**
	 * @return bool
	 */
	public static function usingExt() : bool
	{
		return static::$useExt && function_exists( 'shake256' );
	}


	/**
	 * Shake256 hashing
	 *
	 * @param $data
	 * @param $length
	 * @return string
	 * @throws \Exception
	 */
	public static function hash ( $data, $length ) {

		// Using sha3 php extension
		if ( static::usingExt() ) {
			return shake256( $data, $length, true );
		}

		return SHA3::init( SHA3::SHAKE256 )
			->absorb( $data )
			->squeeze( $length );

	}


	/**
	 * @return SHA3|DesktopdSha3
	 * @throws \Exception
	 */
	public static function init ()
	{
		// Using sha3 php extension
		if ( static::$useExt ) {
			return DesktopdSha3::init( DesktopdSha3::SHAKE256 );
		}

		return SHA3::init(SHA3::SHAKE256);
	}

}
