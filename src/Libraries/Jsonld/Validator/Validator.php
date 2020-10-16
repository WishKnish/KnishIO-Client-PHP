<?php

namespace WishKnish\KnishIO\Client\Libraries\Jsonld\Validator;


/**
 * Class Validator
 * @package WishKnish\KnishIO\Client\Libraries\Jsonld\Validator
 */
abstract class Validator {


	/**
	 * Get validator by type
	 *
	 * @param $type
	 * @return mixed
	 * @throws \RuntimeException
	 */
	public static function get( $type )
	{
		$class = '\WishKnish\KnishIO\Client\Libraries\Jsonld\Validator\\' . $type . 'Validator';
		if ( !class_exists( $class ) ) {
			throw new \RuntimeException( 'Validator class ' . $class . ' does not exist.' );
		}
		return new $class;
	}


	/**
	 * @return mixed
	 */
	abstract public function validate( $value ): bool;


}
