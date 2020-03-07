<?php

namespace WishKnish\KnishIO\Client\Libraries;

/*
$v1 = 1.0/(Decimal::$multiplier);
$v2 = 2.0/(Decimal::$multiplier);
$v10 = 10.0/(Decimal::$multiplier);
dd([
	[$v1, $v1, Decimal::cmp($v1, $v1)],
	[$v2, $v2, Decimal::cmp($v2, $v2)],
	[$v1, $v2, Decimal::cmp($v1, $v2)],
	[$v2, $v1, Decimal::cmp($v2, $v1)],
	[$v1, $v10, Decimal::cmp($v1, $v10)],
	[$v2, $v10, Decimal::cmp($v2, $v10)],
	[$v10, $v1, Decimal::cmp($v10, $v1)],
	[$v10, $v2, Decimal::cmp($v10, $v2)],
]);
*/


/**
 * Class Decimal
 * @package WishKnish\KnishIO\Client\Libraries
 */
class Decimal
{

	// Value determines by min sql decimal precision
	public static $multiplier = 10 ** 18;


	/**
	 * @param $val
	 * @return float|int
	 */
	public static function val ( $val )
    {
		if ( abs($val * static::$multiplier ) < 1 ) {
			return 0.0;
		}
		return $val;
	}


    /**
     * Cmp deciamal with precision
     *
     * @param float $val1
     * @param float $val2
     * @param bool $debug
     * @return int
     */
	public static function cmp ( $val1, $val2, $debug = false )
    {
		$val1 = static::val( $val1 ) * static::$multiplier;
		$val2 = static::val( $val2 ) * static::$multiplier;

		// Equal
		if ( abs( $val1 - $val2 ) < 1 ) {
			return 0;
		}

		// Greater or smaller
		return ( $val1 > $val2 ) ? 1 : -1;
	}


	/**
	 * @param $val1
	 * @param $val2
	 * @return bool
	 */
	public static function equal ( $val1, $val2 )
    {
		return ( static::cmp( $val1, $val2 ) === 0 );
	}

}
