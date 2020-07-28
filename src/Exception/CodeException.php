<?php

namespace WishKnish\KnishIO\Client\Exception;

/**
 * Class CodeException
 * @package WishKnish\KnishIO\Client\Exception
 */
class CodeException extends BaseException
{
	/**
	 * BalanceInsufficientException constructor.
	 * @param string $message
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct ( $message = 'Code exception', $code = 1, $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
