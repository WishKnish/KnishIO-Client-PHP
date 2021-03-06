<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

/**
 * Class SignatureMismatchException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class SignatureMismatchException extends BaseException
{
	/**
	 * SignatureMismatchException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct ( $message = 'OTS mismatch', $code = 1, Throwable $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
