<?php

namespace WishKnish\KnishIO\Client\Libraries\Crypto;

use desktopd\SHA3\Sponge as SHA3;


/**
 * Class Shake256
 * @package WishKnish\KnishIO\Client\Libraries\Crypto
 */
class Shake256 {


	/**
	 * Hash bridge
	 *
	 * @param $data
	 * @param $length
	 * @return string
	 * @throws \Exception
	 */
	public static function hash ( $data, $length ) {


		/*
		return DesktopdSha3::init( DesktopdSha3::SHAKE256 )
			->absorb( $data )
			->squeeze( $length );
		*/

		if ( function_exists( 'shake256' ) ) {
			return shake256( $data, $length * 8, true );
		}

		return SHA3::init( SHA3::SHAKE256 )
			->absorb( $data )
			->squeeze( $length );

	}


	/**
	 * @return DesktopdSha3
	 */
	public static function init ()
	{
		return SHA3::init( SHA3::SHAKE256 );

		return DesktopdSha3::init( DesktopdSha3::SHAKE256 );
	}

}
