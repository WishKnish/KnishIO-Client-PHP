<?php

namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class SignatureMalformedException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class SignatureMalformedException extends BaseException
{
	/**
	 * SignatureMalformedException constructor.
	 * @param string $message
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct ( string $message = null, int $code = null, \Throwable $previous = null )
	{
		$message	= default_if_null ($message, 'OTS malformed');
		$code		= default_if_null ($code, 1);

		parent::__construct( $message, $code, $previous );
	}
}
