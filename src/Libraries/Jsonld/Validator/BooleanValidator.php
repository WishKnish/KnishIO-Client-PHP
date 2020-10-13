<?php

namespace WishKnish\KnishIO\Client\Libraries\Jsonld\Validator;


/**
 * Class BooleanValidator
 * @package WishKnish\KnishIO\Client\Libraries\Jsonld\Validator
 */
class BooleanValidator extends Validator {


	/**
	 * @param array $data
	 * @return mixed|void
	 */
	public function validate(array $data): bool
	{
		return true;
	}

}
