<?php

namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class InvalidResponseException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class InvalidResponseException extends BaseException
{
	/**
	 * InvalidResponseException constructor.
	 * @param string $message
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct ( string $message = null, int $code = null, \Throwable $previous = null )
	{
		$message	= default_if_null ($message, 'GraphQL did not provide a valid response.');
		$code		= default_if_null ($code, 2);

		parent::__construct( $message, $code, $previous );
	}
}
