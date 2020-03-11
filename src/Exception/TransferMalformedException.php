<?php

namespace WishKnish\KnishIO\Client\Exception;

use Throwable;

/**
 * Class TransferMalformedException
 * @package WishKnish\KnishIO\Client\Exception
 *
 * @property string $message
 * @property integer $code
 * @property string $file
 * @property integer $line
 */
class TransferMalformedException extends BaseException
{
	/**
	 * TransferMalformedException constructor.
	 * @param string $message
	 * @param int $code
	 * @param Throwable|null $previous
	 */
	public function __construct ( $message = 'Token transfer atoms are malformed', $code = 1, Throwable $previous = null )
	{
		parent::__construct( $message, $code, $previous );
	}
}
