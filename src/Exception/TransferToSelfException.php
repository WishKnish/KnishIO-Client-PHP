<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

/**
 * Class TransferToSelfException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class TransferToSelfException extends BaseException
{
	/**
	 * TransferToSelfException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct ( $message = 'Sender and recipient(s) cannot be the same', $code = 1, Throwable $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
