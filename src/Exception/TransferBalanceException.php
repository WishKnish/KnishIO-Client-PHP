<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

/**
 * Class TransferBalanceException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class TransferBalanceException extends BaseException
{
	/**
	 * TransferBalanceException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct ( $message = 'Insufficient balance to make transfer', $code = 1, Throwable $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
