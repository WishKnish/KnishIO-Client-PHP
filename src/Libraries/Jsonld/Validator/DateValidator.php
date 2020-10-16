<?php

namespace WishKnish\KnishIO\Client\Libraries\Jsonld\Validator;


/**
 * Class DateValidator
 * @package WishKnish\KnishIO\Client\Libraries\Jsonld\Validator
 */
class DateValidator extends Validator {

	protected $format = 'Y-m-d';


	/**
	 * @param array $data
	 * @return mixed|void
	 */
	public function validate( $value ): bool
	{
		$dateTime = \DateTime::createFromFormat( $this->format, $value );
		return $dateTime && ( $dateTime->format( $this->format ) === $value );
	}


}
