<?php

namespace WishKnish\KnishIO\Client\Exception;


/**
 * Class BalanceInsufficientException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class BalanceInsufficientException extends BaseException
{
	/**
	 * BalanceInsufficientException constructor.
	 * @param string $message
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct ( $message = 'Insufficient balance for requested transfer', $code = 1, $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
