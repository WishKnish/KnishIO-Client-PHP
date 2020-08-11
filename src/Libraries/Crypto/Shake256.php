<?php

namespace WishKnish\KnishIO\Client\Libraries\Crypto;

use desktopd\SHA3\Sponge as SHA3;
use Illuminate\Support\Facades\Log;

class Shake256 {

	public static function hash ( $data, $length ) {

		if ( function_exists( 'shake256' ) ) {
			return shake256($data, $length * 8, true);
		}

		return SHA3::init( SHA3::SHAKE256 )
			->absorb( $data )
			->squeeze( $length );

	}

}
