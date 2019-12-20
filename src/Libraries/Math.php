<?php

namespace WishKnish\KnishIO\Client\Libraries;


/**
 * Class Math
 * @package WishKnish\KnishIO\Client\Libraries
 */
class Math {

	/**
	 *!!! @todo experimental function: we need to determine the precision value (in order with DB decimal precision value) !!!
	 * Cmp deciamal with precision
	 *
	 * @param float $val1
	 * @param float $val2
	 */
	public static function cmpDec ($val1, $val2) {

		// Min precision
		$precision = 0.0000000001; // !!! @todo move precision to config !!!

		// Check abs difference for values
		return abs($val1 - $val2) < $precision;
	}

}
