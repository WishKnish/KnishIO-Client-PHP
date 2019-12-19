<?php

namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class TransferMismatchedException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class TransferMismatchedException extends BaseException
{
	/**
	 * TransferMismatchedException constructor.
	 * @param string $message
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct ( string $message = null, int $code = null, \Throwable $previous = null )
	{
		$message	= default_if_null ($message, 'Token transfer slugs are mismached');
		$code		= default_if_null ($code, 1);

		parent::__construct( $message, $code, $previous );
	}
}
