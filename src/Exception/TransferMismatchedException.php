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
	public function __construct ( $message = 'Token transfer slugs are mismached', $code = 1, $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
