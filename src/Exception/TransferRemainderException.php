<?php

namespace WishKnish\KnishIO\Client\Exception;


/**
 * Class TransferRemainderException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class TransferRemainderException extends BaseException
{
	/**
	 * TransferRemainderException constructor.
	 * @param string $message
	 * @param int $code
	 * @param \Throwable|null $previous
	 */
	public function __construct ( $message = 'Invalid remainder provided', $code = 1, $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
