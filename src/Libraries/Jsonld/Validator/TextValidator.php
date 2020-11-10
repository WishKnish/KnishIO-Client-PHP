<?php

namespace WishKnish\KnishIO\Client\Libraries\Jsonld\Validator;


/**
 * Class TextValidator
 * @package WishKnish\KnishIO\Client\Libraries\Jsonld\Validator
 */
class TextValidator extends Validator {


	/**
	 * @param array $data
	 * @return mixed|void
	 */
	public function validate( $value ): bool
	{
		return is_string( $value );
	}


}
