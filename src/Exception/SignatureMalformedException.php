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
	public function __construct ( $message = 'OTS malformed', $code = 1, $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
