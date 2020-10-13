<?php

namespace WishKnish\KnishIO\Client\Libraries\Jsonld\Validator;


/**
 * Class Validator
 * @package WishKnish\KnishIO\Client\Libraries\Jsonld\Validator
 */
abstract class Validator {


	/**
	 * Get validator by type
	 * @param $key
	 */
	public static function get( $type )
	{
		$class = $type . 'Validator';
		return new $class;
	}


	/**
	 * @return mixed
	 */
	abstract public function validate( array $data ): bool;


}
