<?php

namespace WishKnish\KnishIO\Client\Libraries\Jsonld\Validator;


/**
 * Class NumberValidator
 * @package WishKnish\KnishIO\Client\Libraries\Jsonld\Validator
 */
class NumberValidator extends Validator {


	/**
	 * @param array $data
	 * @return mixed|void
	 */
	public function validate( $value ): bool
	{
		return is_numeric( $value );
	}


}
